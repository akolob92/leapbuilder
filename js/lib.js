
/*
 Получить информацию об ограничении во времени и параметрах
 */
function getModelConfiguration() {
    let timeRestriction = (document.querySelector('input#id_timerestriction') || {}).value;
    let alpha = (document.querySelector('input#id_alpha') || {}).value;
    let beta = (document.querySelector('input#id_beta') || {}).value;

    return {
        timeRestriction, alpha, beta
    }
}

/*
 Получить конфигурацию об обязательных и исключенных концептах
 */
function getConceptsConfiguration(options = {}) {
    let excludedIds = [];
    let includedIds = [];

    let inputs = document.querySelectorAll('select.concept-configuration');

    for (let i = 0; i < inputs.length; i++) {
        let id = (inputs[i].id.match(/\d+/g) || [])[0];

        if (!id) {
            console.error('Id is not found in select: ' + inputs[i].id);
            return null;
        }

        let val = inputs[i].value && inputs[i].value.toLowerCase();

        switch (val) {
            case 'include':
                includedIds.push(id);
                break;
            case 'exclude':
                excludedIds.push(id);
                break;
        }
    }

    return { includedIds, excludedIds };
}

/*
 Вывести информацию об ошибке клиенту
 */
function setError(errors = []) {
    if (!Array.isArray(errors)) errors = [errors];

    let notificationSpan = (document.querySelector('div#model-alert-error > span') || {});
    let message = '';

    for (let i = 0; i < errors.length; i++) {
        message += errors[i].message + '   \n';
    }

    notificationSpan.parentNode.classList.remove('hidden');
    notificationSpan.innerHTML = message;
}

/*
Убрать панель с ошибкой
 */
function removeError() {
    let notificationDiv = (document.querySelector('div#model-alert-error') || {});
    notificationDiv.classList.add('hidden');
}


/*
 Установить результирующее значение в форме
 */
function setResultedValue(value = 0) {
    return (document.querySelector('input#id_calc') || {}).value = value;
}

function saveToJSON() {
    let conceptsConfig = getConceptsConfiguration();
    let modelConfig = getModelConfiguration();
    let solution = window.solution || {};

    let payload = {
        includedIds: conceptsConfig.includedIds,
        excludedIds: conceptsConfig.excludedIds,
        solution: solution,
    };

    let payloadInput = document.querySelector('input[name="payload"]') || {};
    payloadInput.value = JSON.stringify(payload);
}


init();

/*
 Вытяжка данных из пейлоада
 */
function extractDataFromPayload() {
    let payload = (document.querySelector('input[name="payload"]') || {}).value;

    try {
        payload = JSON.parse(payload);

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

            setResultedValue(payload.solution.value);

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

    }
}

/*
 Установка листенеров, подготовка данных
 */
function init() {
    extractDataFromPayload();

    let optimizeButton = (document.querySelector('button#id_run_optimize') || {});

    if (!optimizeButton) {
        setError({ message: 'Can not find optimization button' });
        return;
    }

    optimizeButton.addEventListener('click', function(event) {
        let overlay = (document.querySelector('div#model-overlay') || {});
        overlay.classList.remove('hidden');
        window.cancelCalc = false;

        setTimeout(function() {
            removeError();
            runOptimization();
            saveToJSON();

            overlay.classList.add('hidden');
            window.cancelCalc = true;
        }, 200);
    });


    let cy = window.cy;

    let selects = document.querySelectorAll('select.concept-configuration');

    for (let i = 0; i < selects.length; i++) {
        selects[i].addEventListener('change', function(ev) {
            let id = (selects[i].id.match(/\d+/g) || [])[0];
            let addedClass =  ['exclude', 'include'].includes(selects[i].value) ? selects[i].value + 'd' : '';

            if (window.solution) {
                resetModel();
            }

            cy.batch(function(){
                cy.nodes('[label=\''+ id + '\']')
                    .removeClass('included')
                    .removeClass('excluded')
                    .removeClass('free')
                    .addClass(addedClass);
            });

            saveToJSON();
        });
    }

    let modelDependentInputs = [
        document.querySelector('input#id_timerestriction'),
        document.querySelector('input#id_alpha'),
        document.querySelector('input#id_beta'),
    ];
    for (let i = 0; i < modelDependentInputs.length; i++) {
        modelDependentInputs[i].addEventListener('change', function(ev) {
            setResultedValue(0);

            if (window.solution) {
                resetModel();
            }

            saveToJSON();
        });
    }
}

/*
 Сброс подсчитанной модели
 */
function resetModel() {
    setResultedValue(0);

    let subSelects = document.querySelectorAll('select.concept-configuration');

    for (let k = 0; k < subSelects.length; k++) {
        let id = (subSelects[k].id.match(/\d+/g) || [])[0];

        let addedClass =  ['exclude', 'include'].includes(subSelects[k].value) ? subSelects[k].value + 'd' : '';

        console.log(addedClass)
        cy.batch(function(){
            cy.nodes('[label=\''+ id + '\']')
                .removeClass('included')
                .removeClass('excluded')
                .removeClass('free')
                .addClass(addedClass);
        });
    }

    window.solution = null;
}

/*
 Запустить алгоритм поиска оптимальной модели курса
 */
function runOptimization() {
     try {
            console.time('Calculation');

            let cy = window.cy;

            let adjacencyMatrix = buildMatrix(window.concepts, window.relations);
            let influenceMatrix = buildInfluenceMatrix(adjacencyMatrix.matrix, window.relations);

            let conceptsConfig = getConceptsConfiguration();
            let modelConfig = getModelConfiguration();

            let sets = getSets({
                matrix: influenceMatrix,
                positions: adjacencyMatrix.positions
            }, window.concepts, window.relations, modelConfig.timeRestriction, conceptsConfig.excludedIds, conceptsConfig.includedIds);

            let solution = solve({
                matrix: influenceMatrix,
                positions: adjacencyMatrix.positions
            }, window.concepts, window.conceptTypes, sets, modelConfig.alpha, modelConfig.beta);

            console.timeEnd('Calculation');
            window.solution = solution;

            setResultedValue(solution.value);

            cy.batch(function () {
                cy.nodes()
                    .removeClass('included')
                    .removeClass('excluded');

                cy.filter(function (element, i) {
                    return element.isNode() && conceptsConfig.excludedIds.some(id => +id === +element.data('label'));
                })
                    .addClass('excluded');

                cy.filter(function (element, i) {
                    return element.isNode() && solution.set.some(id => +id === +element.data('label'));
                })
                    .addClass('included');

            });
        } catch (e) {
            setError(e);
        }
}

function buildInfluenceMatrix(matrix) {
    let nodeCount = matrix.length;
    let resultedMatrix = matrix.slice();

    let multipliedMatrix = matrix.slice();

    for (let k = 0; k < nodeCount; k++) {
        multipliedMatrix = multiplyMatrix(multipliedMatrix, matrix);

        for (let i = 0; i < nodeCount; i++) {
            for (let j = 0; j < nodeCount; j++) {
                if (+resultedMatrix[i][j] < +multipliedMatrix[i][j]) {
                    resultedMatrix[i][j] = multipliedMatrix[i][j];
                }
            }
        }
    }


    return resultedMatrix;
}

function buildMatrix(nodes, relations) {
    nodes = nodes.sort();

    let conceptIdByPosition = nodes.reduce((res, node, index) => {
        res[index] = node.id;
        return res;
    }, {});

    let conceptPositionById = nodes.reduce((res, node, index) => {
        res[node.id] = index;
        return res;
    }, {});

    let rows = new Array(nodes.length).fill(null);

    rows = rows.map((row, rowPosition) => {
        row = new Array(nodes.length).fill(null);
        row = row.map((column, columnPosition) => {
            if (columnPosition === rowPosition) return 0;

            let relation = relations.find(relation => {
                return (
                    (+relation.fromconceptid === +conceptIdByPosition[rowPosition]) &&
                    (+relation.toconceptid === +conceptIdByPosition[columnPosition])
                );
            });

            if (relation && +relation.influence) {
                return +relation.influence;
            }

            return 0;
        });

        return row;
    });

    return { matrix: rows, positions: conceptPositionById };
}


function solve(matrix = {}, nodes, conceptTypes, sets, alpha = 1, beta = 1) {
    let max = 0;
    let maxSet = [];

    sets.forEach((nodeSet) => {
        if (window.cancelCalc) return;

        let includedCount = nodeSet.length;

        let setValue = nodeSet.reduce((res, nodeId) => {
            let node = nodes.find(n => +n.id === +nodeId);
            let conceptType = conceptTypes.find(ct => +ct.id === +node.conceptTypeId) || { importance: 1 };
            let conceptAlpha = round(+alpha * +node.importance * +conceptType.importance, 4);

            let influenceSum = nodeSet.reduce((res, secondNodeId) => {
                let secondNode = nodes.find(n => +n.id === +secondNodeId);

                return round(+res + +matrix.matrix[matrix.positions[node.id]][matrix.positions[secondNode.id]], 4);
            }, 0);

            let conceptBeta = round(+beta * round(1 / includedCount, 2) * influenceSum, 4);

            return round(res + conceptAlpha + conceptBeta, 4);
        }, 0);

        if (setValue > max) {
            max = setValue;
            maxSet = nodeSet;
        }
    });

    return window.cancelCalc ? null : { value: max, set: maxSet };
}

function getSets(matrix = {}, nodes, relations, timeRestriction, disabledNodeIds = [], enabledNodeIds = []) {

    timeRestriction = parseFloat(timeRestriction);

    if (!timeRestriction || timeRestriction < 0) {
        throw Error('Time restriction should be positive number');
    }

    let disabledNodes = disabledNodeIds.reduce((res, nodeId) => {
        return unique(res.concat(String(nodeId), getAllChildNodes(matrix, nodeId)))
    }, []);

    let enabledNodes = enabledNodeIds.reduce((res, nodeId) => {
        return unique(res.concat(String(nodeId), getAllParentNodes(matrix, nodeId)))
    }, []);

    if (disabledNodeIds.find(dId => enabledNodes.some(eId => +eId === +dId))) {
        throw Error('Intersection')
    }

    let startSet = [].concat(enabledNodes);
    let totalTime = calcSetTime(startSet);

    if (totalTime > timeRestriction) {
        throw Error('Time restriction');
    }

    let result = [];
    startSet.sort();

    let unusedRoots = nodes.filter(node => !relations.some(r => +r.toconceptid === +node.id)).map(node => node.id)
        .filter(nodeId => !startSet.some(id => +id === +nodeId));

    let nextNodes = startSet.reduce((res, nodeId) => {
        return res.concat(relations.filter(r => +r.fromconceptid === +nodeId).map(r => r.toconceptid).filter(id => !startSet.some(sId => +id === +sId)))
    }, unusedRoots);

    recursiveGetSets(startSet, nextNodes);

    function calcSetTime(set) {
        return set.reduce((res, nodeId) => {
            let node = nodes.find(n => +n.id === +nodeId);
            return res + node.time;
        }, 0);
    }

    function countInputEdges(nodeId) {
        return relations.reduce((sum, edge) => sum + (+edge.toconceptid === +nodeId ? 1 : 0), 0)
    }

    function recursiveGetSets(currentSet, nextNodes) {
        if (window.cancelCalc) return;

        currentSet.sort();

        if (calcSetTime(currentSet) > timeRestriction) return;

        result.push(currentSet);

        if (nextNodes.length) {
            nextNodes.forEach(cnId => {
                if (currentSet.some(id => +id === +cnId)) return;
                if (disabledNodes.some(id => +id === +cnId)) return;

                if (disabledNodeIds.some(id => +id === +cnId)) return;

                let nextSet = [...currentSet, String(cnId)];

                if (countInputEdges(cnId) > 1) {
                    let parentNodes = getAllParentNodes(matrix, cnId);
                    nextSet = unique(nextSet.concat(parentNodes));
                }

                nextSet.sort();

                if (result.some(oldSet => {
                        if (oldSet.length !== nextSet.length) return;
                        if (nextSet.some((setNode, index1) => oldSet.some((oldSetNode, index2) => index1 === index2 && +oldSetNode !== +setNode))) return;
                        return true;
                    })) {
                    return;
                }

                recursiveGetSets(nextSet, [].concat.apply(
                    relations.filter(r => +r.fromconceptid === +cnId).map(r => r.toconceptid),
                    nextNodes.filter(id => +cnId !== +id))
                );
            });
        }
    }

    return window.cancelCalc ? null : result;
}

function getAllChildNodes(matrix = {}, node) {
    let rowNumber = matrix.positions[node];

    return matrix.matrix[rowNumber].reduce((res, value, index) => {
        if (value) res.push(Object.keys(matrix.positions).find(id => +matrix.positions[id] === +index));
        return res;
    }, [])
}


function getAllParentNodes(matrix = {}, node) {
    let rowNumber = matrix.positions[node];
    return matrix.matrix.reduce((res, value, index) => {
        if (value[rowNumber]) res.push(Object.keys(matrix.positions).find(id => +matrix.positions[id] === +index));
        return res;
    }, [])
}

function l() {
    console.log(...arguments);
}

function round(number, precision) {
    precision = parseFloat(precision) || 0;
    number = parseFloat(number) || 0;

    return Number(Math.round(number + ('e+' + precision)) + ('e-' + precision));
}

function multiplyMatrix(a, b) {
    let aNumRows = a.length, aNumCols = a[0].length;
    let bNumRows = b.length, bNumCols = b[0].length;
    let m = new Array(aNumRows);  // initialize array of rows
    for (let r = 0; r < aNumRows; ++r) {
        m[r] = new Array(bNumCols); // initialize the current row
        for (let c = 0; c < bNumCols; ++c) {
            m[r][c] = 0;             // initialize the current cell
            for (let i = 0; i < aNumCols; ++i) {
                if (m[r][c] < a[r][i] * b[i][c]) {
                    m[r][c] = round(a[r][i] * b[i][c], 4);
                }
            }
        }
    }
    return m;
}

function random(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}


function unique(arr) {
    let obj = {};
    let ret_arr = [];
    for (let i = 0; i < arr.length; i++) {
        obj[arr[i]] = true;
    }
    for (let key in obj) {
        ret_arr.push(key);
    }
    return ret_arr;
}



