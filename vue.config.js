const { defineConfig } = require('@vue/cli-service')
const path = require('path');

const isDev = (process.env.NODE_ENV = 'development');

module.exports = defineConfig({
  devServer: {
    host: 'localhost',
    port: 8080,
    hot: true
  },
  publicPath: '/',
  transpileDependencies: true,
  css : {
    extract: false,
  },
  configureWebpack: {
    entry: {
      scripts: path.resolve(__dirname, './src/main.js'),
    },
  
    devtool: isDev ? 'inline-source-map' : false,
    mode: process.env.NODE_ENV,
    // module: {
    //   rules: loaders,
    // },
    // plugins,
    // output: {
    //   // outputDir: path.resolve(__dirname, './public'),
    //   filename: '[name].js',
    // },
    optimization: {
      minimize: true,
      splitChunks: false,
    },
  },
});
