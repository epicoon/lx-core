#lx:module lx.Radio;

#lx:use lx.Checkbox;

class Radio extends lx.Checkbox #lx:namespace lx {
	getBasicCss() {
		return {
			checked: 'lx-Radio-1',
			unchecked: 'lx-Radio-0'
		};
	}	
}
