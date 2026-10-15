# Webpack Encore ドキュメント

pieceofcake2/webpack-encore は、[Symfony Webpack Encore](https://github.com/symfony/webpack-encore)
でビルドしたアセット (CSS、JS、画像) を CakePHP 2 アプリケーションに組み込むためのビューヘルパーを
提供します。`entrypoints.json` と `manifest.json` を読み込んでフィンガープリント付きのアセットの
ファイル名を解決し、適切な `<link>`、`<script>`、`<img>` タグを出力します。

- [インストール](#installation)
- [設定](#configuration)
- [ヘルパーのメソッド](#helper-methods)
- [レイアウトの例](#example-layout)
- [注意事項](#notes)

<a id="installation"></a>

## インストール

1. [Composer](https://getcomposer.org/) で**プラグインをインストール**します。

   ```bash
   composer require pieceofcake2/webpack-encore
   ```

   このプラグインは `cakephp-plugin` タイプなので、`composer/installers` によってプラグインディレクトリに
   インストールされます。正しい場所にインストールされるように、アプリケーションの `composer.json` で
   `installer-paths` を設定してください。

   | レイアウト | `installer-paths` |
   | --- | --- |
   | モダン (`config/`, `src/`, `templates/`, `plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
   | レガシー (`app/Config/`, `app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

   `config/bootstrap.php` (レガシーレイアウトでは `app/Config/bootstrap.php`) でプラグインを読み込みます。
   すでに `CakePlugin::loadAll()` を使っている場合は不要です。

   ```php
   CakePlugin::load('WebpackEncore');
   ```

2. webroot の `build/` ディレクトリ (`webroot/build/`、レガシーレイアウトでは `app/webroot/build/`) に
   **Encore のアセットをビルド**します。プロジェクトのルートで次を実行します。

   ```bash
   npm install
   npm run encore dev
   ```

   これで次のファイルが生成されます。

   - `webroot/build/entrypoints.json`
   - `webroot/build/manifest.json`
   - `webroot/build/` 内のコンパイル済みの `.js`、`.css`、画像ファイル

3. コントローラー、たとえば `src/Controller/AppController.php` (レガシーレイアウトでは
   `app/Controller/AppController.php`) で**ヘルパーを読み込み**ます。

   ```php
   App::uses('Controller', 'Controller');

   class AppController extends Controller
   {
       public $helpers = ['Html', 'WebpackEncore.Encore'];
   }
   ```

   これでビューから `$this->Encore` を使えるようになります。

<a id="configuration"></a>

## 設定

デフォルトでは、ヘルパーは次の場所にファイルがあることを想定しています。

- `entrypoints.json` は `WWW_ROOT . 'build/entrypoints.json'`
- `manifest.json` は `WWW_ROOT . 'build/manifest.json'`

これらのファイルを別の場所に置く場合は、`config/bootstrap.php` (レガシーレイアウトでは
`app/Config/bootstrap.php`) でファイルシステム上のフルパスを設定してください。

```php
// entrypoints.json のファイルシステム上のフルパス
Configure::write('WebpackEncore.entrypointsPath', WWW_ROOT . 'build/entrypoints.json');
// manifest.json のファイルシステム上のフルパス
Configure::write('WebpackEncore.manifestPath', WWW_ROOT . 'build/manifest.json');
```

<a id="helper-methods"></a>

## ヘルパーのメソッド

<a id="assetassetpath"></a>

### `asset($assetPath)`

1 つの論理的なアセットのパス (`manifest.json` のキー) を、最終的なフィンガープリント付きの URL に
解決します。マニフェストにないパスはそのまま返します。

```php
// manifest.json の内容が次のとおりの場合:
// {
//   "build/images/logo.png": "/build/images/logo.3eed42.png"
// }

echo $this->Encore->asset('build/images/logo.png');
// 出力: /build/images/logo.3eed42.png
```

<a id="imageassetpath-array-htmlattributes--"></a>

### `image($assetPath, array $htmlAttributes = [])`

`manifest.json` を使って解決した画像アセットの `<img>` タグを出力します。

```php
// 出力: <img src="/build/images/cake_logo.3eed42.png" alt="CakePHP" />
echo $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']);
```

<a id="cssassetpaths-array-options--"></a>

### `css($assetPaths, array $options = [])`

CSS アセットの `<link>` タグを 1 つ以上出力します。第 1 引数には文字列か文字列の配列を指定できます。
各パスは `manifest.json` を使って解決され、オプションとともに `HtmlHelper::css()` に渡されます。

```php
// CSS ファイルが 1 つの場合
echo $this->Encore->css('build/app.css');

// CSS ファイルが複数の場合
echo $this->Encore->css([
    'build/vendors.css',
    'build/app.css',
]);
```

<a id="scriptassetpaths-array-options--"></a>

### `script($assetPaths, array $options = [])`

JavaScript アセットの `<script>` タグを 1 つ以上出力します。文字列か文字列の配列を受け取ります。
各パスは `manifest.json` を使って解決され、オプションとともに `HtmlHelper::script()` に渡されます。

```php
// JS ファイルが 1 つの場合
echo $this->Encore->script('build/app.js');

// JS ファイルが複数の場合
echo $this->Encore->script([
    'build/runtime.js',
    'build/vendors.js',
    'build/app.js',
], ['defer' => true]);
```

`HtmlHelper` と同じように、`['inline' => false]` を渡すとタグを返す代わりに `css` または `script`
ビューブロックに追加し、`['block' => 'name']` を渡すと別のブロックに追加します。

<a id="entrylinktagsentryname-array-options--"></a>

### `entryLinkTags($entryName, array $options = [])`

`entrypoints.json` を読み込み、指定したエントリー (たとえば `app` や `dashboard`) のすべての CSS
ファイルについて `<link>` タグを出力します。デフォルトではタグを `css` ビューブロックに追加します。
タグを文字列として受け取るには `['inline' => true]` を渡してください。

```php
// entrypoints.json の例:
// {
//   "entrypoints": {
//     "app": {
//       "css": ["/build/vendors.3d4e5f.css", "/build/app.abc123.css"],
//       "js": ["/build/runtime.0a1b2c.js", "/build/vendors.3d4e5f.js", "/build/app.123abc.js"]
//     }
//   }
// }

// app エントリーの CSS ファイルの <link> タグ 2 つを css ブロックに追加します
$this->Encore->entryLinkTags('app');
```

<a id="entryscripttagsentryname-array-options--"></a>

### `entryScriptTags($entryName, array $options = [])`

`entrypoints.json` を読み込み、指定したエントリーのすべての JS ファイルについて `<script defer>`
タグを出力します。デフォルトではタグを `script` ビューブロックに追加します。タグを文字列として
受け取るには `['inline' => true]` を、`defer` を付けないようにするには `['defer' => false]` を
渡してください。

```php
// app エントリーの JS ファイルの <script> タグ 3 つを script ブロックに追加します
$this->Encore->entryScriptTags('app');
```

<a id="example-layout"></a>

## レイアウトの例

`templates/Layouts/default.ctp` (レガシーレイアウトでは `app/View/Layouts/default.ctp`) で次のようにします。

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $this->fetch('title') ?></title>

    <?php
    // 'app' エントリーの CSS と JS を css ブロックと script ブロックに追加します
    $this->Encore->entryLinkTags('app');
    $this->Encore->entryScriptTags('app');
    ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <header>
        <!-- マニフェストを使って画像を出力します -->
        <?= $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']) ?>
    </header>

    <div class="content">
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>
```

- `entryLinkTags('app')` は、`app` エントリーのすべての CSS の `<link>` タグを `css` ブロックに追加します。
- `entryScriptTags('app')` は、`app` エントリーのすべての JS の `<script defer>` タグを `script`
  ブロックに追加します。スクリプトは遅延実行されるので、`<head>` で読み込んでもかまいません。
- `image()` メソッドは、画像アセットを解決して `<img>` タグを出力します。

<a id="notes"></a>

## 注意事項

- フロントエンドのコードを変更したら、アセットを再ビルド (`npm run encore dev` または
  `npm run encore production`) して `entrypoints.json` と `manifest.json` を更新してください。
- ヘルパーは、両方の JSON ファイル内のパスが `/build/...` で始まることを想定しています。別のディレクトリを
  使う場合は、JSON の出力を調整するか、それに合わせて `WebpackEncore.entrypointsPath` と
  `WebpackEncore.manifestPath` を設定してください。
- ヘルパーは、最初にアセットが要求されたときに各 JSON ファイルを読み込み、そのリクエストの間は
  キャッシュします。ヘルパーの読み込み時にはファイルは必要ありませんが、最初のビルドの前にアセットを
  出力しようとすると、見つからないファイルの名前を含む `RuntimeException` がスローされます。

<a id="origin"></a>

## 由来

このパッケージは [pieceofcake2](https://github.com/pieceofcake2) が作成し、メンテナンスしています。
他のプロジェクトのフォークではなく、他のプロジェクトをもとにしたものでもありません。
