<script>
import {debounce} from 'lodash-es'

function inView(elem)
{
    const itemBounding = elem.getBoundingClientRect();

    if (document.getElementById('block')) {
        document.getElementById('block').remove();
    }
    const parentBounding = elem.parentNode.getBoundingClientRect();
    if (
        itemBounding.top >= parentBounding.top
        && itemBounding.bottom <= parentBounding.bottom
    ) {
        return true;
    }

    return false;

}

/**
 * Props: []
 * @slot selection-label - The label that will be displayed for the selected Item
 * @slot option - The label shown for  displayed option.
 */
export default {
    name: 'SearchSelect',
    props: {
        modelValue: {
            required: true
        },
        searchFunction: {
            required: false,
            type: Function,
            default: null
        },
        options: {
            required: false,
            default: () => []
        },
        throttle: {
            required: false,
            type: Number,
            default: 250,
        },
        optionsHeight: {
            required: false,
            type: Number,
            default: 200
        },
        placeholder: {
            required: false,
            type: String,
            default: 'type to search'
        },
        disabled: {
            required: false,
            type: Boolean,
            default: false
        },
        labelField: {
            type: String,
            default: 'name'
        },
        showOptionsOnFocus: {
            type: Boolean,
            default: false
        },
        showOptionsWhenEmpty: {
            type: Boolean,
            default: false
        },
        multiple: {
            type: Boolean,
            default: false
        },
        keyOptionsBy: {
            type: String,
            default: 'id'
        },
    },
    emits: [
        'update:modelValue',
        'removed',
        'added',
        'searchTextUpdated',
    ],
    data() {
        return {
            selections: [],
            searchText: null,
            cursorPosition:null,
            filteredOptions: [],
            clearInputTimeout: null,
            keydownTimer: null,
            currentKey: null,
            hasFocus: false,
            debug: false
        }
    },
    computed: {
        hasAdditionalOption () {
            return Boolean(this.$slots.additionalOption);
        },
        hasOptions () {
            return this.filteredOptions.length > 0 || this.hasAdditionalOption;
        },
        optionsListHeight () {
            return this.showingOptions
                    ? this.optionsHeight
                    : 0;
        },
        selection() {
            return this.modelValue;
        },
        showInput() {
            return !this.hasSelection || this.multiple;
        },
        showingOptions() {
            if ((this.showOptionsWhenEmpty || this.hasAdditionalOption) && (this.searchText || this.hasFocus)) {
                return true;
            }
            return this.filteredOptions.length > 0 && this.hasFocus;
        },
        highlightedOption() {
            if (this.showingOptions) {
                return null;
            }
            return this.filteredOptions[this.cursorPosition];
        },
        hasSelection() {
            return this.selections.length > 0
        },
        filteredUniqueOptions () {
            const selectionIds = this.selections.map(s => s[this.keyOptionsBy]);
            return this.filteredOptions
                    .filter(i => !selectionIds.includes(i[this.keyOptionsBy]))
        }
    },
    watch: {
        searchText () {
            this.search(this.searchText, this.options);
            this.$emit('searchTextUpdated', this.searchText);
        },
        filteredOptions () {
            this.cursorPosition = 0;
        },
        modelValue: {
            handler (to) {
                if (to) {
                    this.selections = Array.isArray(to) ? to : [to]
                }
                this.clearInput();
                this.resetCursor();
            },
            immediate: true
        }
    },
    created() {
        this.search = debounce( async (searchText, options) => {
            if (searchText === '' || searchText === null || typeof searchText == 'undefined') {
                if (this.showOptionsOnFocus) {
                    this.filteredOptions = [...options];
                    return;
                }
                return [];
            }

            if (!this.searchFunction)  {
                this.filteredOptions = this.defaultSearchFunction(searchText, options);
                return;
            }

            this.filteredOptions = await this.searchFunction(searchText, options);
        }, this.throttle);
    },
    methods: {
        handleInputFocus () {
            this.hasFocus = true;
            if (this.showOptionsOnFocus) {
                this.search(this.searchText, this.options);
            }
        },
        handleInputBlur () {
            this.log('handleInputBlur')
            setTimeout(() => {
                this.log('timedout')

                this.clearInput();
                this.resetCursor();
                this.hasFocus = false;
            }, 200)
        },
        defaultSearchFunction (searchText, options) {
            this.log('defaultSearchFunction')
            if (!searchText) {
                return [];
            }

            const pattern = new RegExp(searchText, 'gi');
            return options.filter(o => {
                let matches = null;
                if (typeof o == 'string') {
                    matches = o.match(pattern)
                } else {
                    matches = o[this.labelField].match(pattern);
                }
                return matches !== null
            })
        },
        removeSelection(selectionIdx){
            if (this.disabled) {
                return;
            }
            this.log('removeSelection')
            const removed = this.selections.splice(selectionIdx, 1);

            this.emitModelUpdate();
            this.$emit('removed', removed[0])
            this.$nextTick(() => {
                this.$refs.input.focus();
            })
        },
        setSelection(selection) {
            this.selections.push(selection);
            this.emitModelUpdate();
            this.clearInput();
            this.resetCursor();
            this.$emit('added', selection)
        },
        emitModelUpdate () {
            if (this.multiple) {
                this.$emit('update:modelValue', this.selections);
            } else {
                this.$emit('update:modelValue', this.selections[0] ?? null);
            }
        },
        clearInput() {
            this.log('clearInput')
            this.clearSearchText();
            this.clearOptions();
        },
        clearOptions() {
            this.log('clearOptions')
            this.filteredOptions = [];
        },
        clearSearchText() {
            this.log('clearSearchText')
            this.searchText = null;
        },
        resetCursor() {
            this.log('resetCursor')
            this.cursorPosition = 0;
        },
        startKeydownTimer(evt) {
            this.log('startKeydwonTimer')
            if (evt.key === this.currentKey) {
                return;
            }
            this.cancelKeydownTimer(evt);
            if (evt.key === 'ArrowUp') {
                this.keydownTimer = setInterval(() => {this.moveUp()}, 100);
                this.currentKey = 'ArrowUp';
            }
            if (evt.key === 'ArrowDown') {
                this.keydownTimer = setInterval(() => {this.moveDown()}, 100);
                this.currentKey = 'ArrowDown';
            }
        },
        handleKeyDown (evt) {
            this.log('handleKeyDown')
            if (evt.key === 'Tab') {
                this.handleKeyEvent(evt);
                return;
            }
            this.startKeydownTimer(evt);
        },
        cancelKeydownTimer() {
            this.log('cancelKeydwonTimer')
            if (this.keydownTimer) {
                clearInterval(this.keydownTimer);
                this.currentKey = null;
            }
        },
        moveUp() {
            this.log('moveUp')
            if (!this.cursorPosition) {
                this.cursorPosition = 0;
                return;
            }
            if (this.cursorPosition-1 < 0) {
                return;
            }
            this.cursorPosition--;
            this.scrollToHighlightedOption();
        },
        moveDown() {
            this.log('moveDown')
            if (this.cursorPosition === null) {
                this.cursorPosition = 0;
                return;
            }
            if (this.cursorPosition+1 >= this.filteredOptions.length) {
                return;
            }
            this.cursorPosition++;
            this.scrollToHighlightedOption();
        },
        handleKeyEvent(evt) {
            this.log('handleKeyEvent')
            this.cancelKeydownTimer(evt);
            if (this.showingOptions) {
                if (evt.key === 'ArrowDown') {
                    this.moveDown();
                }
                if (evt.key === 'ArrowUp') {
                    this.moveUp();
                }

                if (['Enter'].includes(evt.key)) {
                    evt.preventDefault();
                    this.setSelection(this.filteredOptions[this.cursorPosition])
                }
                if (['Tab'].includes(evt.key)) {
                    // evt.preventDefault();
                    this.setSelection(this.filteredOptions[this.cursorPosition])
                }
                if (evt.key === 'Escape') {
                    this.clearOptions();
                }
            }
        },
        scrollToHighlightedOption () {
            this.log('scrollToHighlightedOption')
            if (!inView(document.getElementById(`option-${this.cursorPosition}`))) {
                document.getElementById(`option-${this.cursorPosition}`).scrollIntoView();
            }

        },
        resolveDefaultOptionLabel (option) {
            if (typeof option == 'object') {
                return option[this.labelField]
            }

            return option
        },

        log (input) {
            if (this.debug) {
                // eslint-disable-next-line no-console
                console.log(input)
            }
        }
    },
}
</script>
<template>
  <div class="search-select-component">
    <div class="search-select-container bg-white">
      <div v-if="hasSelection">
        <div
          v-for="currSelection, idx in selections" :key="idx"
          class="selection" :class="{'disabled': disabled }"
        >
          <label>
            <slot name="selection-label" :selection="currSelection">
              {{ resolveDefaultOptionLabel(currSelection) }}
            </slot>
          </label>
          <div
            :disabled="disabled"
            class="remove-btn"
            @click="removeSelection(idx)"
          >
            x
          </div>
        </div>
      </div>
      <input
        v-show="showInput"
        id="search-select-input"
        ref="input"
        v-model="searchText"
        type="text"
        class="input"
        :placeholder="placeholder"
        :disabled="disabled"
        @keydown="handleKeyDown"
        @keyup="handleKeyEvent"
        @focus="handleInputFocus"
        @blur="handleInputBlur"
      >
    </div>
    <div v-show="showingOptions" class="result-container">
      <div>
        <ul class="option-list" :style="`max-height: ${optionsListHeight}px`">
          <li
            v-for="(opt, idx) in filteredUniqueOptions"
            :id="`option-${idx}`"
            :key="idx"
            class="filtered-option"
            :class="{highlighted: (idx === cursorPosition)}"
            @click="setSelection(opt)"
          >
            <slot :option="opt" :index="idx" name="option">
              {{ resolveDefaultOptionLabel(opt) }}
            </slot>
          </li>
          <li v-if="$slots.additionalOption" class="filtered-option additional-option">
            <slot name="additionalOption" />
          </li>
        </ul>
        <div v-if="$slots.fixedBottomOption" class="bg-white p-2 border">
          <slot name="fixedBottomOption" />
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
    .search-select-component {
        position: relative;
        overflow: visible;
        height: 2.5rem;
        width: 100%;
    }

    .search-select-container {
        @apply border border-gray-300 leading-6 px-2 flex items-center flex-wrap py-1 rounded space-x-2;
    }

    .selection {
        @apply bg-gray-500 text-white flex mr-1 rounded text-sm block;
    }

    .selection.disabled {
        background: #aaa;
    }

    .selection > * {
        padding-left: .5rem;
        padding-right: .5rem;
    }

    .selection > label {
        @apply py-0.5;
        margin-bottom: 0;
    }

    .remove-btn {
        /* @apply bg-inherit border border-t-0 border-r-0 border-b-0 border-gray-400 rounded-r-md pt-0.5 cursor-pointer; */
    }

    .search-select-container .input {
        @apply border-none block w-40;
        display: block;
        min-width: 2rem; /* Set the width small b/c this will grow with flex */
        outline: none;
        padding: 0px;
        flex-grow: 1;
        flex-shrink: 1;
    }

    .result-container {
        position:relative;
        z-index: 10;
        overflow-x: auto;
    }

    .result-container > * {
        @apply bg-gray-50;
        box-shadow: 0 0 5px #666;
        margin: 0 .5rem;
        padding: 0;
    }
    .option-list {
        overflow: auto;
    }

    .filtered-option {
        /* @apply hover:bg-blue-200 cursor-pointer focus:bg-blue-200; */
        @apply border-t border-gray-300;
        cursor: pointer;
        margin:0;
        padding: .25rem .5rem;
    }
    .filtered-option:first-child {
        @apply border-0;
    }

    .filtered-option:nth-child(odd) {
        @apply bg-white;
    }

    .filtered-option:hover {
        @apply bg-blue-200/30;
    }
    .filtered-option.highlighted {
        @apply bg-blue-200/50;
    }
    .filtered-option.additional-option {
        @apply border-t border-gray-400 bg-white;
    }
</style>
