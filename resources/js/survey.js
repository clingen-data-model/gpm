class Question {
    constructor(question) {
        this.question_text = question.question;
        this.name = question.name;
        this.options = question.options;
        this.validationRules = question.validation;
        this.type = question.type;
        this.show = question.show;
        this.class = question.class;
        this.content = question.content;
    }
}

class YesNoQuestion extends Question {
    constructor(question) {
        super(question)
        this.type = 'multiple-choice';
        this.options = [{
                label: 'Yes',
                value: 1
            },
            {
                label: 'No',
                value: 0
            }
        ];
    }
}

function makeQuestion(questionDef) {
    if (questionDef.type === 'yes-no') {
        return new YesNoQuestion(questionDef);
    }

    return new Question(questionDef);
}

class Survey {
    constructor(surveyDefinition) {
        this._name = surveyDefinition.name;
        this._questions = surveyDefinition.questions.map(q => makeQuestion(q));

    }

    get name() {
        return this._name;
    }

    get questions() {
        return this._questions
    }

    getResponseTemplate() {
        const rsp = {};
        this.questions
            .filter(q => q.type === 'content')
            .forEach(q => {
                rsp[q.name] = null
            });
        return rsp;

    }
}

export default Survey
