const portfinder = require('portfinder')

portfinder.getPortPromise().then((port) => {
    console.log(port)
})
