let uid = 0;

export default {
    beforeMount() {
        this.uid = uid.toString();
        uid += 1;
    }
}