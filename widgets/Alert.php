<?php

namespace app\widgets;

use Yii;


class Alert extends \yii\base\Widget
{
    
    public $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning'
    ];

    
    public $closeButton = [];

    
    public function run()
    {
        $session = Yii::$app->session;
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach (array_keys($this->alertTypes) as $type) {
            $flash = $session->getFlash($type);

            foreach ((array) $flash as $i => $message) {
                echo $this->renderAlert($type, $message, $appendClass, $i);
            }

            $session->removeFlash($type);
        }
    }

    
    protected function renderAlert($type, $message, $appendClass, $index)
    {
        $class = $this->alertTypes[$type] . $appendClass;
        $id = $this->getId() . '-' . $type . '-' . $index;
        
        $closeButton = '';
        if (!empty($this->closeButton)) {
            $closeButton = $this->renderCloseButton();
        }

        return "
            <div class=\"alert {$class}\" id=\"{$id}\" role=\"alert\">
                {$message}
                {$closeButton}
            </div>
        ";
    }

    
    protected function renderCloseButton()
    {
        $tag = isset($this->closeButton['tag']) ? $this->closeButton['tag'] : 'button';
        $label = isset($this->closeButton['label']) ? $this->closeButton['label'] : '&times;';
        $class = isset($this->closeButton['class']) ? $this->closeButton['class'] : 'alert-close';
        
        $attributes = '';
        if ($tag === 'button') {
            $attributes = ' type="button" data-dismiss="alert" aria-label="Close"';
        }
        
        return "<{$tag} class=\"{$class}\"{$attributes}>{$label}</{$tag}>";
    }
}
