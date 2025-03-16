"use strict";
/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
(self["webpackChunkepam"] = self["webpackChunkepam"] || []).push([["src_views_PersonEdit_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _components_people_ProfileForm_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @/components/people/ProfileForm.vue */ \"./src/components/people/ProfileForm.vue\");\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  name: 'PersonEdit',\n  components: {\n    ProfileForm: _components_people_ProfileForm_vue__WEBPACK_IMPORTED_MODULE_0__[\"default\"]\n  },\n  props: {\n    uuid: {\n      required: true,\n      type: String\n    }\n  },\n  computed: {\n    person() {\n      return this.$store.getters['people/currentItem'];\n    }\n\n  },\n  watch: {\n    uuid: {\n      immediate: true,\n\n      handler() {\n        this.$store.dispatch('people/getPerson', {\n          uuid: this.uuid\n        });\n      }\n\n    }\n  },\n  methods: {\n    goBack() {\n      this.$router.go(-1);\n    }\n\n  }\n});\n\n//# sourceURL=webpack://epam/./src/views/PersonEdit.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use%5B0%5D!./node_modules/vue-loader/dist/index.js??ruleSet%5B0%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e":
/*!*******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e ***!
  \*******************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; }\n/* harmony export */ });\n/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ \"./node_modules/vue/dist/vue.runtime.esm-bundler.js\");\n\nfunction render(_ctx, _cache, $props, $setup, $data, $options) {\n  const _component_profile_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)(\"profile-form\");\n\n  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"div\", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_profile_form, {\n    person: $options.person,\n    modelValue: $options.person,\n    \"onUpdate:modelValue\": _cache[0] || (_cache[0] = $event => $options.person = $event),\n    onSaved: _cache[1] || (_cache[1] = $event => $options.goBack()),\n    onCanceled: _cache[2] || (_cache[2] = $event => $options.goBack())\n  }, null, 8\n  /* PROPS */\n  , [\"person\", \"modelValue\"])]);\n}\n\n//# sourceURL=webpack://epam/./src/views/PersonEdit.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use%5B0%5D!./node_modules/vue-loader/dist/templateLoader.js??ruleSet%5B1%5D.rules%5B3%5D!./node_modules/vue-loader/dist/index.js??ruleSet%5B0%5D.use%5B0%5D");

/***/ }),

/***/ "./src/views/PersonEdit.vue":
/*!**********************************!*\
  !*** ./src/views/PersonEdit.vue ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _PersonEdit_vue_vue_type_template_id_fcbb358e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonEdit.vue?vue&type=template&id=fcbb358e */ \"./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e\");\n/* harmony import */ var _PersonEdit_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PersonEdit.vue?vue&type=script&lang=js */ \"./src/views/PersonEdit.vue?vue&type=script&lang=js\");\n/* harmony import */ var _Users_yugen_code_clingen_gpm_resources_app_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ \"./node_modules/vue-loader/dist/exportHelper.js\");\n\n\n\n\n;\nconst __exports__ = /*#__PURE__*/(0,_Users_yugen_code_clingen_gpm_resources_app_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(_PersonEdit_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"], [['render',_PersonEdit_vue_vue_type_template_id_fcbb358e__WEBPACK_IMPORTED_MODULE_0__.render],['__file',\"src/views/PersonEdit.vue\"]])\n/* hot reload */\nif (false) {}\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (__exports__);\n\n//# sourceURL=webpack://epam/./src/views/PersonEdit.vue?");

/***/ }),

/***/ "./src/views/PersonEdit.vue?vue&type=script&lang=js":
/*!**********************************************************!*\
  !*** ./src/views/PersonEdit.vue?vue&type=script&lang=js ***!
  \**********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_40_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PersonEdit_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"]; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_40_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PersonEdit_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./PersonEdit.vue?vue&type=script&lang=js */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=script&lang=js\");\n \n\n//# sourceURL=webpack://epam/./src/views/PersonEdit.vue?");

/***/ }),

/***/ "./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e":
/*!****************************************************************!*\
  !*** ./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_40_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PersonEdit_vue_vue_type_template_id_fcbb358e__WEBPACK_IMPORTED_MODULE_0__.render; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_40_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PersonEdit_vue_vue_type_template_id_fcbb358e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[3]!../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./PersonEdit.vue?vue&type=template&id=fcbb358e */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-40.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./src/views/PersonEdit.vue?vue&type=template&id=fcbb358e\");\n\n\n//# sourceURL=webpack://epam/./src/views/PersonEdit.vue?");

/***/ })

}]);