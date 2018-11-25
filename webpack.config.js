const path = require('path');

module.exports = {
	mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
	entry: ['./js/src/functions.js'],
	externals: {
		lodash: 'lodash'
	},
	output: {
		path: path.resolve(__dirname, 'js'),
		filename: 'functions.js',
	},
	module: {
		rules: [
			/**
			 * Running Babel on JS files.
			 */
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				}
			}
		]
	}
};
