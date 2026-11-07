<?php
/**
 * @link https://github.com/borodulin/yii2-pug
 * @copyright Copyright (c) 2016 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-pug/blob/master/LICENSE
 */

namespace conquer\pug;

use Pug\Pug;
use Yii;
use yii\base\ViewRenderer;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Renders `.pug` views with pug-php 3
 *
 * ```php
 * 'view' => [
 *     'renderers' => [
 *         'pug' => conquer\pug\PugRenderer::class,
 *     ],
 * ],
 * ```
 *
 * Templates are compiled into PHP files in [[cachePath]] and rendered as PHP views, so `$this` is the view.
 * A template is compiled again when it or a template it includes or extends changes.
 *
 * @link https://github.com/pug-php/pug
 * @author Andrey Borodulin
 */
class PugRenderer extends ViewRenderer
{
    /**
     * Directory of the compiled templates
     * @var string
     */
    public $cachePath = '@runtime/Pug/cache';

    /**
     * Seconds after which a compiled template is compiled again even if the templates did not change;
     * 0 to keep it until a template changes
     * @var int
     */
    public $cacheDuration = 0;

    /**
     * Compile the templates on every render and pretty-print the HTML
     * @var bool
     */
    public $debug = false;

    /**
     * Options of pug-php
     * @link https://phug-lang.com/#options
     * @var array
     */
    public $options = [];

    /**
     * Filters: name => callable
     * @var array
     */
    public $filters = [];

    /**
     * Keywords: name => callable
     * @var array
     */
    public $keyWords = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->cachePath = Yii::getAlias(rtrim($this->cachePath, '\\/'));
        FileHelper::createDirectory($this->cachePath);
        $this->options = ArrayHelper::merge([
            'extension' => '.pug',
            'expressionLanguage' => 'php',
            'pretty' => $this->debug,
        ], (array)$this->options);
    }

    /**
     * @param \yii\base\View $view
     * @param string $file
     * @param array $params
     * @return string
     */
    public function render($view, $file, $params)
    {
        $filename = $this->cachePath . DIRECTORY_SEPARATOR . md5($file) . '.php';
        if ($this->debug || !$this->isFresh($filename)) {
            $pug = new Pug(ArrayHelper::merge(['basedir' => dirname($file)], $this->options));
            foreach ((array)$this->filters as $name => $callback) {
                $pug->filter($name, $callback);
            }
            foreach ((array)$this->keyWords as $name => $callback) {
                $pug->addKeyword($name, $callback);
            }
            $code = $pug->compileFile($file);
            $dependencies = array_values(array_unique(array_merge([$file], $pug->getCompiler()->getCurrentImportPaths())));
            $this->write($filename . '.deps', json_encode($dependencies));
            $this->write($filename, $code);
        }
        return $view->renderPhpFile($filename, $params);
    }

    /**
     * @param string $filename compiled template
     * @return bool whether the compiled template is newer than the templates it was compiled from
     */
    protected function isFresh($filename)
    {
        if (!is_file($filename) || !is_file($filename . '.deps')) {
            return false;
        }
        $time = filemtime($filename);
        if ($this->cacheDuration > 0 && time() - $time >= $this->cacheDuration) {
            return false;
        }
        $dependencies = json_decode((string)file_get_contents($filename . '.deps'), true);
        if (!is_array($dependencies)) {
            return false;
        }
        clearstatcache();
        foreach ($dependencies as $dependency) {
            if (!is_file($dependency) || filemtime($dependency) >= $time) {
                return false;
            }
        }
        return true;
    }

    /**
     * Writes a file atomically, so concurrent requests never include a partly written template
     * @param string $filename
     * @param string $content
     */
    protected function write($filename, $content)
    {
        $temp = $filename . '.' . uniqid('', true) . '.tmp';
        file_put_contents($temp, $content);
        rename($temp, $filename);
    }
}
