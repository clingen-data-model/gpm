<script>
import {debounce} from 'lodash'

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
            default: ''
        },
        disabled: {
            required: false,
            type: Boolean,
            default: false
        }
    },
    emits: [
        'update:modelValue',
    ],
    data() {
        return {
            searchText: null,
            cursorPosition:null,
            filteredOptions: [],
            clearInputTimeout: null,
            keydownTimer: null,
            currentKey: null,
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
            return !this.hasSelection;
        },
        showingOptions() {
            if (this.hasAdditionalOption && this.searchText) {
                return true;
            }
            return this.filteredOptions.length > 0;
        },
        highlightedOption() {
            if (this.showingOptions) {
                return null;
            }
            return this.filteredOptions[this.cursorPosition];
        },
        hasSelection() {
            return Boolean(this.modelValue)
        },
    },
    watch: {
        searchText: function () {
            this.search(this.searchText, this.options);
        },
        filteredOptions: function () {
            this.cursorPosition = 0;
        },
        modelValue () {
            this.clearInput();
            this.resetCursor();
        }
    },
    methods: {
        handleInputBlur (evt) {
            console.log(evt)
            this.clearInput();
            this.resetCursor();
        },
        defaultSearchFunction (searchText, options) {
            if (!searchText) {
                return [];
            }
            return options.filter(o => {
                const match = o.name.match(new RegExp(searchText, 'gi'));
                return match !== null
            })
        },
        removeSelection(){
            this.$emit('update:modelValue', null);
            this.$nextTick(() => {
                this.$refs.input.focus();
            })
        },
        setSelection(selection) {
            console.log('setting selection');
            this.$emit('update:modelValue', selection);
            this.clearInput();
            this.resetCursor();
        },
        clearInput() {
            this.clearSearchText();
            this.clearOptions();
        },
        clearOptions() {
            console.debug('clearOptions');
            this.filteredOptions = [];
        },
        clearSearchText() {
            console.debug('clearSearchText')
            this.searchText = null;
        },
        resetCursor() {
            this.cursorPosition = 0;
        },
        startKeydownTimer(evt) {
            if (evt.key == this.currentKey) {
                return;
            }
            this.cancelKeydownTimer(evt);
            if (evt.key == 'ArrowUp') {
            console.info('start key down timer', evt.key)
                this.keydownTimer = setInterval(() => {this.moveUp()}, 100);
                this.currentKey = 'ArrowUp';
            }
            if (evt.key == 'ArrowDown') {
            console.info('start key down timer', evt.key)
                this.keydownTimer = setInterval(() => {this.moveDown()}, 100);
                this.currentKey = 'ArrowDown';
            }
        },
        handleKeyDown (evt) {
            if (evt.key == 'Tab') {
                this.handleKeyEvent(evt);
                return;
            }
            this.startKeydownTimer(evt);
        },
        cancelKeydownTimer() {
            if (this.keydownTimer) {
                clearInterval(this.keydownTimer);
                this.currentKey = null;
            }
        },
        moveUp() {
            if (!this.cursorPosition) {
                this.cursorPosition = 0;
                return;
            }
            if (this.cursorPosition-1 < 0) {
                return;
            }
            this.cursorPosition--;
            this.scrollToHighlightedOption();
            return;
        },
        moveDown() {
            if (this.cursorPosition === null) {
                this.cursorPosition = 0;
                return;
            }
            if (this.cursorPosition+1 >= this.filteredOptions.length) {
                return;
            }
            this.cursorPosition++;
            this.scrollToHighlightedOption();
            return;
        },
        handleKeyEvent(evt) {
            this.cancelKeydownTimer(evt);
            if (this.showingOptions) {
                if (evt.key == 'ArrowDown') {
                    this.moveDown();
                }
                if (evt.key == 'ArrowUp') {
                    this.moveUp();
                }

                if (['Enter'].indexOf(evt.key) > -1) {
                    evt.preventDefault();
                    this.setSelection(this.filteredOptions[this.cursorPosition])
                }
                if (['Tab'].indexOf(evt.key) > -1) {
                    // evt.preventDefault();
                    this.setSelection(this.filteredOptions[this.cursorPosition])
                }
                if (evt.key == 'Escape') {
                    console.log('escape');
                    this.clearOptions();
                }
            }
        },
        scrollToHighlightedOption () {
            if (!inView(document.getElementById('option-'+this.cursorPosition))) {
                document.getElementById('option-'+this.cursorPosition).scrollIntoView();
            }

        }
    },
    created() {
        this.search = debounce( async (searchText, options) => {
            if (searchText == '' || searchText === null || typeof searchText == 'undefined') {
                return [];
            }

            if (!this.searchFunction)  {
                this.filteredOptions = this.defaultSearchFunction(searchText, options);
                return;
            }

            this.filteredOptions = await this.searchFunction(searchText, options);
        }, this.throttle);
    }
}
</script>
<template>
    <!-- Trying to get v-click-outside to work, but option slot markup doesn't appear to be in complenent $el when running $el.contains() in v-click-outisde directive -->
    <!-- <div class="search-select-component" v-click-outside="{handler: ()=> {}, exclude: []}"> -->
    <div class="search-select-component" >
        <div class="search-select-container border">
            <div class="selection" :class="{disabled: disabled}" v-if="hasSelection">
                <label>
                    <slot name="selection-label" :selection="modelValue">
                        {{modelValue}}
                    </slot>
                </label>  
                <button @click="removeSelection()" :disabled="disabled">x</button>
            </div>
            <input 
                v-show="showInput"
                type="text" 
                v-model="searchText" 
                ref="input" 
                class="input" 
                @keydown="handleKeyDown"
                @keyup="handleKeyEvent"
                :placeholder="placeholder"
                :disabled="disabled"
                id="search-select-input"
            >
        </div>
        <div v-show="showingOptions" class="result-container">
            <ul class="option-list" :style="`max-height: ${optionsListHeight}px`">
                <li v-for="(opt, idx) in filteredOptions" 
                    :key="idx" 
                    class="filtered-option"
                    :class="{highlighted: (idx === cursorPosition)}"
                    :id="`option-${idx}`"
                    @click="setSelection(opt)"
                >
                    <slot :option="opt" :index="idx" name="option">{{opt}}</slot>
                </li>
                <li v-if="$slots.additionalOption" class="filtered-option additional-option">
                    <slot name="additionalOption"></slot>
                </li>
            </ul>
        </div>
    </div>
</template>
<style scoped>
    .search-select-component {
        position: relative;
        overflow: visible;
        height: 2.5rem
    }

    .search-select-container {
        @apply border border-gray-300 leading-6 px-2 flex items-center flex-wrap py-1 rounded bg-white;
    }

    .search-select-container > input {
        border: none;
    }
    
    .search-select-container > .selection {
        @apply bg-gray-500 text-white flex mr-1 rounded-sm text-sm;
    }

    .search-select-container > .selection.disabled {
        background: #aaa;
    }

    .search-select-container > .selection > * {
        /* @apply px-2 leading-6; */
        padding-left: .5rem;
        padding-right: .5rem;
        /* line-height: 1.5rem; */
    }

    .search-select-container > .selection > label {
        margin-bottom: 0;
    }

    .search-select-container > .selection > button {
        /* @apply border-l border-gray-400; */
        border-width: 0 0 0 1px;
        background-color: transparent;
        color: white;
    }
    
    .search-select-container .input {
        /* @apply border-none block outline-none focus:outline-none p-0 flex-1; */
        display: block;
        width: 100%;
        outline: none;
        padding: 0px;
        flex-grow: 1;
        z-index: 5
    }

    .result-container {
        position:relative;
    }

    .option-list {
        @apply bg-gray-50;
        box-shadow: 0 0 5px #666;
        margin: 0 .5rem;
        padding: 0;
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
        @apply bg-blue-200 bg-opacity-30;
    }
    .filtered-option.highlighted {
        @apply bg-blue-200 bg-opacity-50;
    } 
    .filtered-option.additional-option {
        @apply border-t border-gray-400 bg-white;
    }
</style>
