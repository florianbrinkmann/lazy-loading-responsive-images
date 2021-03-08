import { decode } from "blurhash";

( function() {
	// Get all images with `data-blurhash` attribute.
	const images = document.querySelectorAll( "img[data-blurhash]" );
	if ( images.length === 0 ) {
		// Nothing to do here.
		return;
	}

	let processedHashes = [],
		styles = document.createElement( 'style' );

	for ( let image of images ) {
		let x = image.getAttribute( 'data-blurhash-x' ),
			y = image.getAttribute( 'data-blurhash-y' );
		if ( x === '' || y === '' ) {
			return;
		}

		// Check if hash is already processed.
		const blurhash = image.getAttribute( "data-blurhash" );
		if ( processedHashes.includes( blurhash ) ) {
			return;
		}

		processedHashes.push( blurhash );

		// Reduce width and height.
		const width = parseInt( x * 6 );
		const height = parseInt( y * 6 );
		
		const pixels = decode( blurhash, width, height ),
			canvas = document.createElement( "canvas" ),
			ctx = canvas.getContext( "2d", { alpha: false } ),
			imageData = ctx.createImageData( width, height );

		canvas.width = width;
		canvas.height = height;

		imageData.data.set( pixels );
		ctx.putImageData( imageData, 0, 0 );
		canvas.toBlob( ( blob ) => {
			styles.innerHTML += `img[data-blurhash="${ image.getAttribute( "data-blurhash" ) }"]:not(.lazyloaded) { background-image: url("${ URL.createObjectURL( blob ) }"); }`;
		} );
	}



	document.body.append( styles );
} )();