<?php

namespace ThreePHP\Loaders;

use ThreePHP\Fonts\Font;

use function ThreePHP\root;

class FontLoader extends Loader
{

    public function parse($content)
    {
        return new Font(json_decode($content, true));
    }

    public static function loadHelvetikerBold()
    {
        return static::load(root() . '/Fonts/helvetiker_bold.typeface.json');
    }
    public static function loadOptimerBold()
    {
        return static::load(root() . '/Fonts/optimer_bold.typeface.json');
    }
}

/*
import {
	FileLoader,
	Loader,
	ShapePath
} from 'three';

class FontLoader extends Loader {

	constructor( manager ) {
		super( manager );
	}

	load( url, onLoad, onProgress, onError ) {
		const scope = this;
		const loader = new FileLoader( this.manager );
		loader.setPath( this.path );
		loader.setRequestHeader( this.requestHeader );
		loader.setWithCredentials( this.withCredentials );
		loader.load( url, function ( text ) {
			const font = scope.parse( JSON.parse( text ) );
			if ( onLoad ) onLoad( font );
		}, onProgress, onError );
	}

	parse( json ) {
		return new Font( json );
	}
}

//


export { FontLoader, Font };
*/