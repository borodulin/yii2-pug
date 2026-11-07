<?php

namespace conquer\pug\tests;

use conquer\pug\PugRenderer;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\helpers\FileHelper;

class PugRendererTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/pug-test-' . uniqid();
        FileHelper::copyDirectory(__DIR__ . '/views', $this->dir . '/views');
        new Application([
            'id' => 'test',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'runtimePath' => $this->dir . '/runtime',
            'components' => [
                'view' => ['renderers' => ['pug' => ['class' => PugRenderer::class]]],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->dir);
        Yii::$app = null;
    }

    private function render(array $params = [])
    {
        return Yii::$app->view->renderFile($this->dir . '/views/page.pug', $params + ['title' => '<b>Title</b>', 'raw' => '<i>raw</i>']);
    }

    private function compiled()
    {
        return $this->dir . '/runtime/Pug/cache/' . md5($this->dir . '/views/page.pug') . '.php';
    }

    public function testRender()
    {
        $this->assertSame(
            '<div class="layout"><h1>&lt;b&gt;Title&lt;/b&gt;</h1><p><i>raw</i></p><p>view yii\web\View</p><p>part 1</p></div>',
            $this->render()
        );
        $this->assertFileExists($this->compiled());
    }

    public function testCompiledTemplateIsReused()
    {
        $this->render();
        // a marker in the compiled file shows that it is not compiled again
        file_put_contents($this->compiled(), 'cached');
        touch($this->compiled(), time() + 10);
        $this->assertSame('cached', $this->render());
    }

    public function testChangedIncludeIsCompiledAgain()
    {
        $this->render();
        touch($this->compiled(), time() - 100);
        file_put_contents($this->dir . '/views/part.pug', 'p part 2');
        $this->assertStringContainsString('<p>part 2</p>', $this->render());
        file_put_contents($this->dir . '/views/layout.pug', "section\n  block content\n");
        touch($this->compiled(), time() - 100);
        $this->assertStringStartsWith('<section>', $this->render());
    }

    public function testCacheDuration()
    {
        Yii::$app->view->renderers['pug']['cacheDuration'] = 60;
        $this->render();
        file_put_contents($this->compiled(), 'cached');
        touch($this->compiled(), time() - 61);
        $this->assertStringStartsWith('<div class="layout">', $this->render());
    }

    public function testDebugCompilesEveryTime()
    {
        Yii::$app->view->renderers['pug']['debug'] = true;
        $this->render();
        file_put_contents($this->compiled(), 'cached');
        touch($this->compiled(), time() + 10);
        $html = $this->render();
        $this->assertStringContainsString("<h1>&lt;b&gt;Title&lt;/b&gt;</h1>", $html);
        $this->assertStringContainsString("\n", $html);
    }

    public function testFilters()
    {
        Yii::$app->view->renderers['pug']['filters'] = ['upper' => function ($text) {
            return strtoupper($text);
        }];
        file_put_contents($this->dir . '/views/part.pug', "div\n  :upper\n    hello");
        $this->assertMatchesRegularExpression('#<div>\s*HELLO</div>#', $this->render());
    }
}
