<script>
export default {
    name: 'ApplicationStepReview',
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        expertPanel () {
            return this.group.expert_panel;
        },
        isGcep () {
            return this.group.isGcep();
        },
        isVcep () {
            return this.group.isVcep();
        },
        members () {
            return this.group.members.map(m => ({
                id: m.id,
                name: m.person.name,
                institution: m.person.institution ? m.person.institution.name : null,
                credentials: m.person.credentials,
                expertise: m.expertise,
                roles: m.roles.map(r => r.name).join(', '),
                coi_completed: this.formatDate(m.coi_last_completed)
            }));
        }
    },
    methods: {

    }
}
</script>
