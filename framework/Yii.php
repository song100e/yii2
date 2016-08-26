<?php
/**
 * Yii bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

require(__DIR__ . '/BaseYii.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Yii extends \yii\BaseYii
{
}

spl_autoload_register(['Yii', 'autoload'], true, true);
// 注意第三个参数将 Yii::autoload() 作为autoloader插入到栈的最前面
// Yii类是里面没有任何代码,实际上将 BaseYii::autoload() 注册为autoloader
Yii::$classMap = require(__DIR__ . '/classes.php');	// 核心类映射关系
Yii::$container = new yii\di\Container();			// 构造DI容器
