export default function (error) {
    return error.response && error.response.status == 402
}