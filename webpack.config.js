const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  const plugins = [
    new MiniCssExtractPlugin({
      filename: isProduction
        ? 'style-[name].[contenthash].css'
        : 'style-[name].css'
    }),
    new WebpackManifestPlugin({ fileName: 'asset-manifest.json' }),
    // Only touches entries that import '@wordpress/*' (i.e. courses-admin) —
    // maps those imports to the wp.* globals instead of bundling them, and
    // emits build/courses-admin.asset.php with the dependency handles +
    // version that functions.php needs for wp_enqueue_script(). Entries with
    // no '@wordpress/*' imports (courses-frontend) still get an asset.php,
    // it's just an empty dependency array — harmless, and left unused there.
    new DependencyExtractionWebpackPlugin()
  ];

  return {
    mode: isProduction ? 'production' : 'development',
    entry: {
      'home-v2': './src/index.js',
      'courses-admin': './src/courses-admin.js',
      'courses-frontend': './src/courses-frontend.js'
    },
    output: {
      path: path.resolve(__dirname, 'build'),
      filename: isProduction ? '[name].[contenthash].js' : '[name].js',
      assetModuleFilename: isProduction
        ? 'images/[name].[contenthash][ext]'
        : 'images/[name][ext]',
    },
    devtool: isProduction ? false : 'eval',
    watchOptions: {
      ignored: ['**/node_modules/**', '**/build/**', '**/.git/**'],
      aggregateTimeout: 300
    },
    module: {
      rules: [
        { test: /\.(js|jsx)$/, exclude: /node_modules/, use: 'babel-loader' },
        { test: /\.css$/,  use: [ MiniCssExtractPlugin.loader, 'css-loader' ] },
        { test: /\.scss$/, use: [ MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader' ] },
        { test: /\.(png|jpe?g|gif|webp|svg)$/i, type: 'asset/resource' },
        {
          test: /\.(woff2?|ttf|otf|eot)$/i,
          type: 'asset/resource',
          generator: {
            filename: isProduction ? 'fonts/[name].[contenthash][ext]' : 'fonts/[name][ext]'
          }
        }
      ]
    },
    resolve: {
      extensions: ['.js', '.jsx']
    },
    plugins: plugins
  };
};