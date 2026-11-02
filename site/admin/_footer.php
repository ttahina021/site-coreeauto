</div>
<script>
// Confirmation avant les actions destructives
document.querySelectorAll('[data-confirmer]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
        if (!confirm(el.getAttribute('data-confirmer'))) ev.preventDefault();
    });
});
// Affiche ou masque les champs de promotion
document.querySelectorAll('[data-promo-type]').forEach(function (select) {
    var maj = function () {
        var bloc = document.getElementById(select.getAttribute('data-promo-type'));
        if (bloc) bloc.hidden = select.value === '';
        var unite = document.querySelector('[data-unite]');
        if (unite) unite.textContent = select.value === 'pourcent' ? '%' : 'Ar';
    };
    select.addEventListener('change', maj);
    maj();
});
// Coche / décoche toutes les lignes
var tout = document.getElementById('tout-cocher');
if (tout) tout.addEventListener('change', function () {
    document.querySelectorAll('input[name="ids[]"]').forEach(function (c) { c.checked = tout.checked; });
});
</script>
</body>
</html>
