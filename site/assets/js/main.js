// Soumission automatique des filtres du catalogue
document.querySelectorAll('#form-filtres input, #form-filtres select').forEach(function (champ) {
    champ.addEventListener('change', function () { champ.form.submit(); });
});

// Fermeture des messages après quelques secondes
setTimeout(function () {
    document.querySelectorAll('main > .conteneur > .alerte').forEach(function (a) {
        a.style.transition = 'opacity .4s';
        a.style.opacity = '0';
        setTimeout(function () { a.remove(); }, 400);
    });
}, 4000);
