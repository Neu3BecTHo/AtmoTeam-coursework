<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use Yii;

class User extends ActiveRecord implements IdentityInterface
{
    public $avatarFile;
    public $newPassword;

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [

            [['username', 'email', 'password'], 'required', 'on' => 'register'],
            ['password', 'string', 'min' => 6, 'on' => 'register'],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Только буквы, цифры и _', 'on' => 'register'],
            ['username', 'string', 'min' => 3, 'max' => 32, 'on' => 'register'],
            ['email', 'email', 'on' => 'register'],
            [['username', 'email'], 'unique', 'on' => 'register'],

            ['username', 'string', 'max' => 32, 'on' => 'update'],
            ['email', 'email', 'on' => 'update'],
            [['username', 'email'], 'unique', 'on' => 'update'],
            ['bio', 'string', 'max' => 500, 'on' => 'update'],
            ['location', 'string', 'max' => 100, 'on' => 'update'],
            ['website', 'url', 'on' => 'update'],
            ['website', 'string', 'max' => 255, 'on' => 'update'],

            ['avatarFile', 'file', 'extensions' => ['png', 'jpg', 'jpeg', 'gif'], 'maxSize' => 2 * 1024 * 1024, 'on' => 'update'],

            ['newPassword', 'string', 'min' => 6, 'on' => 'update'],

            ['is_private', 'boolean', 'on' => 'update'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['register'] = ['username', 'email', 'password'];
        $scenarios['update'] = ['username', 'email', 'bio', 'location', 'website', 'avatar', 'is_private', 'newPassword'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Имя пользователя',
            'email' => 'Email',
            'avatar' => 'Аватар',
            'bio' => 'О себе',
            'location' => 'Местоположение',
            'website' => 'Сайт',
            'is_private' => 'Приватный профиль',
            'newPassword' => 'Новый пароль',
            'created_at' => 'Дата регистрации',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && $this->scenario === 'register') {
            $password = $this->password;
            if ($password) {
                $this->setPassword($password);
            }
        }

        if ($this->avatarFile) {
            $filename = 'avatar_' . $this->id . '_' . time() . '.' . $this->avatarFile->extension;
            $path = \Yii::getAlias('@webroot/uploads/avatars/');

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            if ($this->avatar && file_exists(\Yii::getAlias('@webroot') . $this->avatar)) {
                @unlink(\Yii::getAlias('@webroot') . $this->avatar);
            }

            $this->avatarFile->saveAs($path . $filename);
            $this->avatar = '/uploads/avatars/' . $filename;
        }

        if ($this->newPassword) {
            $this->setPassword($this->newPassword);
        }

        return true;
    }

    public function getAvatarUrl()
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->id;
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }

    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = \Yii::$app->security->generatePasswordHash($password);
    }

    public function getPassword()
    {
        return $this->password_hash;
    }

    
    public function getPosts()
    {
        return $this->hasMany(Post::class, ['user_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    public function getLikes()
    {
        return $this->hasMany(Like::class, ['user_id' => 'id']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::class, ['user_id' => 'id']);
    }

    
    public function getFollowing()
    {
        return $this->hasMany(Follow::class, ['follower_id' => 'id']);
    }

    
    public function getFollowers()
    {
        return $this->hasMany(Follow::class, ['following_id' => 'id']);
    }

    
    public function isFollowedBy($userId)
    {
        return Follow::isFollowing($userId, $this->id);
    }

    
    public function getFollowStats()
    {
        return [
            'following' => Follow::find()->where(['follower_id' => $this->id])->count(),
            'followers' => Follow::find()->where(['following_id' => $this->id])->count(),
        ];
    }

    
    public function isPrivate()
    {
        return (bool) $this->is_private;
    }

    
    public function canViewProfile($viewerId = null)
    {

        if (!$this->isPrivate()) {
            return true;
        }

        if (!$viewerId) {
            return false;
        }

        if ($this->id === $viewerId) {
            return true;
        }

        return Follow::isFollowing($viewerId, $this->id);
    }

    
    public function blockUser($blockedUserId)
    {
        return Block::block($this->id, $blockedUserId);
    }

    
    public function unblockUser($blockedUserId)
    {
        return Block::unblock($this->id, $blockedUserId);
    }

    
    public function hasBlocked($userId)
    {
        return Block::isBlocked($this->id, $userId);
    }

    
    public function getBlockedUsers($limit = 20, $offset = 0)
    {
        return Block::getBlockedUsers($this->id, $limit, $offset);
    }

    
    public function canInteractWith($userId)
    {

        if ($this->hasBlocked($userId) || User::findOne($userId)->hasBlocked($this->id)) {
            return false;
        }
        
        return true;
    }

    
    public static function getPopularUsers($limit = 10)
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'popular_users';
        
        return $cache->getOrSet($cacheKey, function () use ($limit) {
            return self::find()
                ->select(['user.*', 'COUNT(DISTINCT follow.follower_id) as followers_count'])
                ->leftJoin('follow', 'follow.following_id = user.id')
                ->where(['user.is_blocked' => 0])
                ->groupBy('user.id')
                ->orderBy(['followers_count' => SORT_DESC, 'user.created_at' => SORT_ASC])
                ->limit($limit)
                ->all();
        }, 3600); // Кэшируем на 1 час
    }

    
    public static function getActiveUsers($limit = 10)
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'active_users';
        
        return $cache->getOrSet($cacheKey, function () use ($limit) {
            return self::find()
                ->select(['user.*', 'COUNT(DISTINCT post.id) as posts_count'])
                ->leftJoin('post', 'post.user_id = user.id')
                ->where(['user.is_blocked' => 0])
                ->andWhere(['>', 'post.created_at', time() - 86400 * 7]) // Посты за неделю
                ->groupBy('user.id')
                ->having(['>', 'posts_count', 0])
                ->orderBy(['posts_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 1800); // Кэшируем на 30 минут
    }

    
    public function getCachedStats()
    {
        $cache = Yii::$app->cache;
        $cacheKey = "user_stats_{$this->id}";
        
        return $cache->getOrSet($cacheKey, function () {
            return [
                'posts_count' => Post::find()->where(['user_id' => $this->id])->count(),
                'followers_count' => Follow::getFollowersCount($this->id),
                'following_count' => Follow::getFollowingCount($this->id),
                'likes_received' => \app\models\Like::find()
                    ->innerJoin('post', 'like.post_id = post.id')
                    ->where(['post.user_id' => $this->id])
                    ->count(),
                'comments_received' => \app\models\Comment::find()
                    ->innerJoin('post', 'comment.post_id = post.id')
                    ->where(['post.user_id' => $this->id])
                    ->count(),
            ];
        }, 900); // Кэшируем на 15 минут
    }

    
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        Yii::$app->cache->delete("user_stats_{$this->id}");
        
        if (isset($changedAttributes['is_blocked'])) {

            Yii::$app->cache->delete('popular_users');
            Yii::$app->cache->delete('active_users');
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        Yii::$app->cache->delete("user_stats_{$this->id}");
        Yii::$app->cache->delete('popular_users');
        Yii::$app->cache->delete('active_users');
    }
}
