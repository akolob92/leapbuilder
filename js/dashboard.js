extractDataFromPayload();

/*
 Вытяжка данных из пейлоада
 */
function extractDataFromPayload() {
    let payload = window.payload;

    try {
        if (typeof payload === 'string') payload = JSON.parse(payload);

        window.solution = payload.solution;

        let cy = window.cy;

        cy.batch(function () {
            cy.nodes()
                .removeClass('included')
                .removeClass('excluded');

            cy.filter(function (element, i) {
                return element.isNode() && payload.excludedIds.some(id => +id === +element.data('label'));
            })
                .addClass('excluded');

            if (!payload.solution) return;

            cy.filter(function (element, i) {
                return element.isNode() && payload.solution.set.some(id => +id === +element.data('label'));
            })
                .addClass('included');
        });

        let selects = document.querySelectorAll('select.concept-configuration');

        for (let i = 0; i < selects.length; i++) {
            let id = (selects[i].id.match(/\d+/g) || [])[0];

            if (payload.excludedIds.some(excludedId => ++excludedId === +id)) {
                selects[i].value = 'exclude';
            }

            if (payload.includedIds.some(excludedId => ++excludedId === +id)) {
                selects[i].value = 'include';
            }
        }
    } catch (e) {

        console.log(e)
    }
}