import { nodeResolve } from '@rollup/plugin-node-resolve';
import replace from '@rollup/plugin-replace';
import vue from 'rollup-plugin-vue';

export default {
  input: 'build/media_source/com_microschema/js/schema-editor.es6.js',
  output: {
    file: 'com_microschema/media/js/schema-editor.js',
    format: 'es',
    sourcemap: false,
  },
  plugins: [
    replace({
      preventAssignment: true,
      'process.env.NODE_ENV': JSON.stringify('production'),
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: false,
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    }),
    nodeResolve(),
    vue({ css: false }),
  ],
};
