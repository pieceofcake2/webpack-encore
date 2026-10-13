<?php

App::uses('Controller', 'Controller');
App::uses('EncoreHelper', 'WebpackEncore.View/Helper');
App::uses('View', 'View');

class EncoreHelperTest extends CakeTestCase
{
    private string $dataPath;

    private View $View;

    public function setUp(): void
    {
        parent::setUp();
        $this->dataPath = dirname(__DIR__, 3) . DS . 'data' . DS;
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'entrypoints.json');
        Configure::write('WebpackEncore.manifestPath', $this->dataPath . 'manifest.json');
        $this->View = $this->view('/');
    }

    public function tearDown(): void
    {
        Configure::delete('WebpackEncore');
        parent::tearDown();
    }

    private function view(string $webroot): View
    {
        $request = new CakeRequest('/', false);
        $request->webroot = $webroot;

        return new View(new Controller($request));
    }

    private function helper(?View $View = null): EncoreHelper
    {
        return new EncoreHelper($View ?? $this->View);
    }

    /**
     * HtmlHelper::css() and script() join the tags for an array of paths like this.
     */
    private function tags(string ...$tags): string
    {
        return "\n\t" . implode("\n\t", $tags) . "\n";
    }

    public function testEntryLinkTags(): void
    {
        $this->assertSame(
            $this->tags('<link rel="stylesheet" type="text/css" href="/build/app.abc123.css"/>'),
            $this->helper()->entryLinkTags('app', ['inline' => true]),
        );
    }

    public function testEntryLinkTagsAppendsToBlockByDefault(): void
    {
        $this->assertEmpty($this->helper()->entryLinkTags('app'));
        $this->assertStringContainsString('/build/app.abc123.css', $this->View->fetch('css'));
    }

    public function testEntryLinkTagsWithoutCss(): void
    {
        $this->assertSame('', $this->helper()->entryLinkTags('admin'));
        $this->assertSame('', $this->helper()->entryLinkTags('missing'));
    }

    public function testEntryScriptTags(): void
    {
        $expected = $this->tags(...[
            '<script type="text/javascript" src="/build/runtime.0a1b2c.js" defer="defer"></script>',
            '<script type="text/javascript" src="/build/app.123abc.js" defer="defer"></script>',
        ]);

        $this->assertSame($expected, $this->helper()->entryScriptTags('app', ['inline' => true]));
    }

    public function testEntryScriptTagsAppendsToBlockByDefault(): void
    {
        $this->assertEmpty($this->helper()->entryScriptTags('admin'));
        $this->assertStringContainsString('/build/admin.456def.js', $this->View->fetch('script'));
    }

    public function testEntryScriptTagsWithoutJs(): void
    {
        $this->assertSame('', $this->helper()->entryScriptTags('missing'));
    }

    public function testEntryTagsStripWebrootPrefix(): void
    {
        $View = $this->view('/sub/');
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'entrypoints-sub.json');

        $this->assertSame(
            $this->tags('<link rel="stylesheet" type="text/css" href="/sub/build/app.abc123.css"/>'),
            $this->helper($View)->entryLinkTags('app', ['inline' => true]),
        );
    }

    public function testAsset(): void
    {
        $this->assertSame('/build/images/logo.3eed42.png', $this->helper()->asset('build/images/logo.png'));
    }

    public function testAssetNotInManifest(): void
    {
        $this->assertSame('build/unknown.png', $this->helper()->asset('build/unknown.png'));
    }

    public function testImage(): void
    {
        $this->assertSame(
            '<img src="/build/images/logo.3eed42.png" alt="Logo"/>',
            $this->helper()->image('build/images/logo.png', ['alt' => 'Logo']),
        );
    }

    public function testCss(): void
    {
        $this->assertSame(
            $this->tags('<link rel="stylesheet" type="text/css" href="/build/app.abc123.css"/>'),
            $this->helper()->css('build/app.css'),
        );
    }

    public function testScript(): void
    {
        $expected = $this->tags(...[
            '<script type="text/javascript" src="/build/runtime.0a1b2c.js"></script>',
            '<script type="text/javascript" src="/build/app.123abc.js"></script>',
        ]);

        $this->assertSame($expected, $this->helper()->script(['build/runtime.js', 'build/app.js']));
    }

    public function testConstructWithoutBuild(): void
    {
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'missing.json');
        Configure::write('WebpackEncore.manifestPath', $this->dataPath . 'missing.json');

        $this->assertInstanceOf(EncoreHelper::class, $this->helper());
    }

    public function testMissingEntrypointsThrowsExceptionWhenUsed(): void
    {
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'missing.json');
        $helper = $this->helper();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Encore entrypoints.json file not found: ' . $this->dataPath . 'missing.json. ' .
            'Build the assets first (e.g. `npm run encore dev`), or set `WebpackEncore.entrypointsPath` to its path.',
        );

        $helper->entryScriptTags('app');
    }

    public function testMissingManifestThrowsExceptionWhenUsed(): void
    {
        Configure::write('WebpackEncore.manifestPath', $this->dataPath . 'missing.json');
        $helper = $this->helper();
        $this->assertEmpty($helper->entryScriptTags('app'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Encore manifest.json file not found: ' . $this->dataPath . 'missing.json.');

        $helper->asset('build/images/logo.png');
    }

    public function testInvalidEntrypointsThrowsException(): void
    {
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'invalid.json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Encore entrypoints.json format');

        $this->helper()->entryLinkTags('app');
    }

    public function testEntrypointsWithoutEntrypointsKeyThrowsException(): void
    {
        Configure::write('WebpackEncore.entrypointsPath', $this->dataPath . 'manifest.json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Encore entrypoints.json format');

        $this->helper()->entryLinkTags('app');
    }

    public function testInvalidManifestThrowsException(): void
    {
        Configure::write('WebpackEncore.manifestPath', $this->dataPath . 'invalid.json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Encore manifest.json format');

        $this->helper()->asset('build/app.js');
    }
}
