#lx:private

/**
 * Карта, описывающая зависимости от модулей - плагины, подписанные на модули
 * */
lx.modules = {
	cache: true,
	data: {},

	/**
	 * Получает список требуемых модулей и выделяет из него тех, о которых нет информации
	 * */ 
	defineNecessary: function(list) {
		if (this.data == {}) return list;

		var result = [];
		for (let i=0, l=list.len; i<l; i++)
			if (!(list[i] in this.data))
				result.push(list[i]);
		return result;
	},

	/**
	 * При создании плагина он подписывается на модули
	 * */
	depend: function(map) {
		for (var i=0, l=map.len; i<l; i++) {
			let moduleName = map[i];
			if (moduleName in this.data) this.data[moduleName]++;
			else this.data[moduleName] = 1;
		}
	},

	/**
	 * При удалении плагина он отписывается от модулей
	 * Если на модуль больше никто не подписан и модули не кэшируются, такой модуль будет удален
	 * */
	independ: function(map) {
		//TODO
		// for (var namespace in map) {
		// 	if (!(namespace in this.data)) continue;

		// 	let widgets = map[namespace];
		// 	for (var i=0, l=widgets.len; i<l; i++) {
		// 		let widget = widgets[i];
		// 		if (!(widget in this.data[namespace])) continue;

		// 		this.data[namespace][widget]--;
		// 		if (this.data[namespace][widget] == 0 && !this.cache) {
		// 			delete this.data[namespace][widget];
		// 			delete window[namespace][widget];
		// 			if (window[namespace].lxEmpty) delete window[namespace];
		// 		}
		// 	}
		// }
	}
};
