<script setup>
    import MarkdownBlock from '../MarkdownBlock.vue';

    const props = defineProps({
        survey: {
            required: true,
            type: Array
        }
    })
</script>

<template>
    <div
        v-for="question in survey.questions"
        :key="question.name"
        :class="question.class"
    >
        <transition name="slide-fade-down">
            <MarkdownBlock v-if="question.type == 'content'"
                :markdown="question.content"
            />
            <input-row
                v-else
                :label="question.question_text"
                :errors="errors[question.name]"
                :vertical="true"
                v-show="showQuestion(question)"
            >

                <textarea  v-if="question.type == 'text'"
                    class="w-full h-24"
                    v-model="response[question.name]"
                    :name="question.name"
                ></textarea>

                <div v-if="question.type == 'multiple-choice'">
                    <label v-for="option in question.options" :key="option.value" class="mb-1">
                        <input type="radio"
                            :value="option.value"
                            :name="question.name"
                            v-model="response[question.name]"
                        >
                        <div>{{option.label}}</div>
                    </label>
                </div>

                <input
                    type="text"
                    v-if="question.type == 'string'"
                    v-model="response[question.name]"
                    :name="question.name"
                >
            </input-row>
        </transition>
    </div>
</template>
