let uid = 0;

export default {
    beforeMount() {
        // console.log('beforeMount');
        this.uid = uid.toString();
        uid += 1;
    }
}