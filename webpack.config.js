const path = require('path');

module.exports = {
	mode: 'production',
	entry: ['./assets/sass/site.scss', './assets/sass/mkdocs_extra.scss'],
	output: {
		path: path.resolve(__dirname, 'assets'),
	},
	module: {
		rules: [
			{
				test: /\.scss$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'css/[name].css',
						}
					},
					{
						loader: 'extract-loader'
					},
					{
						loader: 'css-loader?-url'
					},
					{
						loader: 'postcss-loader'
					},
					{
						loader: 'sass-loader'
					}
				]
			}
		]
	}
};