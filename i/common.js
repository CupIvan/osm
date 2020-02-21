$(function(){
	const div = $('div')
	const position = document.location.href.includes('OSMvsNarod') ? 'right-bottom' : ''
	div.innerHTML = `
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.2.3/gh-fork-ribbon.min.css" />
	<a class="github-fork-ribbon ${position} fixed" data-ribbon="Открыть на GitHub"
	href="https://github.com/CupIvan/osm" title="Открыть репозиторий на GitHub"
	target="_blank">Открыть на GitHub</a>
	<style>
		.github-fork-ribbon:before       { background: #333 }
		.github-fork-ribbon:hover:before { background: #933; transition: background-color 200ms linear; }
	</style>`
	document.body.appendChild(div)
})
