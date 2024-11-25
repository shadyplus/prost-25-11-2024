const scrollToForm = (id) => {
    const links = document.querySelectorAll('a');

    links.forEach((link) => {
        link.onclick = (event) => {
            event.preventDefault();
            document
                .querySelector(id)
                .scrollIntoView({ block: 'center', behavior: 'smooth' });
        };
    });
};

setCookie(
    'thanksLink',
    window.location.origin +
        window.location.pathname +
        window.location.search +
        '&thanks=true'
);
