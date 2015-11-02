
function setUpSearch(Jets) {
    return new Jets({
        searchTag: '#jetsSearch',
        contentTag: '#jetsContent',
        cols: [1, 2]
    })
}

if (window.Jets) {
    window.search = setUpSearch(window.Jets);
}
