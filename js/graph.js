var shapes = ['ellipse', 'rectangle', 'triangle'];

window.conceptTypes = window.conceptTypes || [];
window.concepts = window.concepts || [];
window.relations = window.relations || [];
window.conceptsForHighlight = window.conceptsForHighlight || [];
window.relationsForHighlight = window.relationsForHighlight || [];

var nodes = window.concepts.map(concept => ({
    classes: window.conceptsForHighlight.some(cId => +cId === +concept.id) ? 'highlight' : '',
    data: {
        color: 'white',
        shape: shapes[window.conceptTypes.findIndex(type => +type.id === +concept.conceptTypeId)] || shapes[0],
        label: concept.id,
        id: 'n' + concept.id
    }
}));

var edges = window.relations.map(relation => {
    return ({
        classes: window.relationsForHighlight.some(rId => +rId === +relation.id) ? 'highlight' : '',
        data: {
            id: relation.id,
            label: relation.influence,
            source: 'n' + relation.fromconceptid,
            target: 'n' + relation.toconceptid
        }
    })
});


var cy = window.cy = cytoscape({
    container: document.getElementById('cy'),

    layout: {
        name: 'dagre'
    },

    style: [
        {
            selector: 'node',
            style: {
                'shape': 'data(shape)',
                'label': 'data(label)',
                'text-valign': 'center',
                'text-halign': 'right',
                'background-color': 'data(color)',
                'border-color': 'black',
                'border-width': '1px'
            }
        },
        {
            selector: 'node.excluded',
            style: {
                'background-color': 'red',
            }
        },
        {
            selector: 'node.included',
            style: {
                'background-color': 'green',
            }
        },
        {
            selector: '.faded',
            style: {
                'text-opacity': 1,
                'border-opacity': 0.5,
                'background-opacity': 0.5,
                'line-color': 'green',
            }
        },
        {
            selector: 'node.highlight',
            style: {
                'background-color': 'orange',
            }
        },
        {
            selector: 'edge',
            style: {
                'label': 'data(label)',
                'curve-style': 'bezier',
                'width': 2,
                'line-color': 'black',
                'target-arrow-color': 'black',
                'target-arrow-shape': 'triangle',
            }
        },
        {
            selector: 'edge.highlight',
            style: {
                'line-color': 'orange',
                'width': 4,
            }
        },
    ],

    elements: {
        nodes: nodes,
        edges: edges
    },
});
