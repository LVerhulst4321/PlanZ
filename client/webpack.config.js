const path = require("path");

module.exports = {
  entry: "./src/app.jsx",
  mode: "production",
  module: {
    rules: [
       {
          test:/\.(js|jsx)$/,
          include: path.resolve(__dirname, 'src'),
          loader: 'babel-loader',
          options: {
             presets: ['@babel/preset-react']
          }
       }
    ]
  },
  resolve: {
    extensions: [".js", ".jsx"]
  },
  output: {
    filename: "planzReactApp.js",
    path: path.resolve(__dirname, "dist")
  }
};