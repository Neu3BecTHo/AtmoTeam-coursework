<?php

namespace app\controllers\admin;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\search\RoleSearch;
use app\models\User;
use yii\rbac\DbManager;


class RoleController extends Controller
{
    
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['admin.access'],
                    ],
                    [
                        'actions' => ['create', 'update'],
                        'allow' => true,
                        'roles' => ['user.manage_roles'],
                    ],
                    [
                        'actions' => ['delete', 'assign', 'revoke'],
                        'allow' => true,
                        'roles' => ['user.delete'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['GET', 'POST'],
                    'update' => ['GET', 'POST'],
                    'delete' => ['POST'],
                    'assign' => ['POST'],
                    'revoke' => ['POST'],
                ],
            ],
        ];
    }

    
    public function actionIndex()
    {
        $searchModel = new RoleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $auth = Yii::$app->authManager;

        $users = \app\models\UserRole::find()
            ->with(['user', 'role'])
            ->where(['role_id' => $id])
            ->andWhere(['or', 
                ['expires_at' => null],
                ['>', 'expires_at', time()]
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $permissions = $auth->getPermissionsByRole($id);

        return $this->render('view', [
            'model' => $model,
            'users' => $users,
            'permissions' => $permissions,
        ]);
    }

    
    public function actionCreate()
    {
        $model = new \app\models\Role();
        $auth = Yii::$app->authManager;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {

                    if (Yii::$app->request->post('permissions')) {
                        foreach (Yii::$app->request->post('permissions') as $permissionName) {
                            $auth->addChild($model->name, $permissionName);
                        }
                    }
                    
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Роль успешно создана');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при создании роли: ' . $e->getMessage());
            }
        }

        $allPermissions = $auth->getPermissions();

        return $this->render('create', [
            'model' => $model,
            'allPermissions' => $allPermissions,
        ]);
    }

    
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $auth = Yii::$app->authManager;

        $currentPermissions = $auth->getPermissionsByRole($id);
        $currentPermissionNames = array_map(function($p) { return $p->name; }, $currentPermissions);

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {

                    if (Yii::$app->request->post('permissions')) {
                        $newPermissions = Yii::$app->request->post('permissions');

                        foreach ($currentPermissions as $permission) {
                            $auth->removeChild($model->name, $permission->name);
                        }

                        foreach ($newPermissions as $permissionName) {
                            $auth->addChild($model->name, $permissionName);
                        }
                    } else {

                        foreach ($currentPermissions as $permission) {
                            $auth->removeChild($model->name, $permission->name);
                        }
                    }
                    
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Роль успешно обновлена');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при обновлении роли: ' . $e->getMessage());
            }
        }

        $allPermissions = $auth->getPermissions();

        return $this->render('update', [
            'model' => $model,
            'allPermissions' => $allPermissions,
            'currentPermissions' => $currentPermissionNames,
        ]);
    }

    
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->is_system) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить системную роль');
            return $this->redirect(['index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {

            \app\models\AuthItemChild::deleteAll(['parent' => $model->name]);

            \app\models\AuthAssignment::deleteAll(['item_name' => $model->name]);

            $model->delete();
            
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Роль успешно удалена');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при удалении роли: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    
    public function actionAssign()
    {
        $userId = Yii::$app->request->post('user_id');
        $roleName = Yii::$app->request->post('role_name');
        $expiresAt = Yii::$app->request->post('expires_at');

        if (!$userId || !$roleName) {
            return $this->asJson(['success' => false, 'message' => 'Не указаны пользователь или роль']);
        }

        $auth = Yii::$app->authManager;
        $user = User::findOne($userId);

        if (!$user) {
            return $this->asJson(['success' => false, 'message' => 'Пользователь не найден']);
        }

        try {
            $auth->assign($roleName, $userId);

            if ($expiresAt) {
                $assignment = \app\models\AuthAssignment::find()
                    ->where(['user_id' => $userId, 'item_name' => $roleName])
                    ->one();
                
                if ($assignment) {
                    $assignment->expires_at = strtotime($expiresAt);
                    $assignment->save();
                }
            }
            
            return $this->asJson(['success' => true, 'message' => 'Роль успешно назначена']);
        } catch (\Exception $e) {
            return $this->asJson(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    
    public function actionRevoke()
    {
        $userId = Yii::$app->request->post('user_id');
        $roleName = Yii::$app->request->post('role_name');

        if (!$userId || !$roleName) {
            return $this->asJson(['success' => false, 'message' => 'Не указаны пользователь или роль']);
        }

        $auth = Yii::$app->authManager;
        $user = User::findOne($userId);

        if (!$user) {
            return $this->asJson(['success' => false, 'message' => 'Пользователь не найден']);
        }

        try {
            $auth->revoke($roleName, $userId);
            return $this->asJson(['success' => true, 'message' => 'Роль успешно отозвана']);
        } catch (\Exception $e) {
            return $this->asJson(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    
    public function actionGetUsers($roleName)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $auth = Yii::$app->authManager;
        $users = \app\models\AuthAssignment::find()
            ->with(['user'])
            ->where(['item_name' => $roleName])
            ->andWhere(['or', 
                ['expires_at' => null],
                ['>', 'expires_at', time()]
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $users;
    }

    
    public function actionGetPermissions($roleName)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $auth = Yii::$app->authManager;
        $permissions = $auth->getPermissionsByRole($roleName);

        return $permissions;
    }

    
    public function actionSearchUsers($q = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$q) {
            return [];
        }

        $users = User::find()
            ->select(['id', 'username', 'email'])
            ->where(['or', 
                ['like', 'username', $q],
                ['like', 'email', $q]
            ])
            ->limit(20)
            ->asArray()
            ->all();

        return $users;
    }

    
    protected function findModel($id)
    {
        if (($model = \app\models\Role::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }
}
