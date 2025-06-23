console.log('hello');
Echo.private('details1')
    .listen('Approved', (e) => {
        console.log(e);
    });
