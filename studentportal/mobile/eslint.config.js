const { defineConfig, globalIgnores } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');

module.exports = defineConfig([
  globalIgnores(['dist/**', 'dist-native/**', '.expo/**', 'test-results/**', 'playwright-report/**']),
  expoConfig,
]);
