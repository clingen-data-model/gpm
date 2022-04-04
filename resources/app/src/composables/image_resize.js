
export const imageFromBlob = (blob) => {
    const img = new Image();
    img.src = URL.createObjectURL(blob);

    return img;
}

export const resizeImageBlob = async (img, width) => {
    var canvas = document.createElement('canvas'),
    ctx = canvas.getContext("2d"),

    oc = document.createElement('canvas'),
    octx = oc.getContext('2d');

    canvas.width = width; // destination canvas size
    canvas.height = canvas.width * img.height / img.width;

    var cur = {
        width: Math.floor(img.width * 0.5),
        height: Math.floor(img.height * 0.5)
    }

    oc.width = cur.width;
    oc.height = cur.height;

    octx.drawImage(img, 0, 0, cur.width, cur.height);

    while (cur.width * 0.5 > width) {
        cur = {
            width: Math.floor(cur.width * 0.5),
            height: Math.floor(cur.height * 0.5)
        };
        octx.drawImage(oc, 0, 0, cur.width * 2, cur.height * 2, 0, 0, cur.width, cur.height);
    }
    ctx.drawImage(oc, 0, 0, cur.width, cur.height, 0, 0, canvas.width, canvas.height);
    img.src =
}