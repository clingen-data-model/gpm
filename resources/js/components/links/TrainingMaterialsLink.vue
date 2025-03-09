<script>
export default {
    name: 'TrainingMaterialsLink',
}
</script>
<template>
    <a 
        href="https://clinicalgenome.org/docs/?doc-type=training-materials#list_documentation_table"
        target="clinicalgenome"
    >
        <slot>
            training materials
        </slot>
    </a>
</template>