#private

/**
 * Карта, описывающая зависимости от виджетов - количества модулей, подписанных на виджеты
 * */
lx.widgets = {
	cache: true,
	data: {},

	/**
	 * Получает список требуемых виджетов и выделяет из него тех, о которых нет информации
	 * */ 
	defineNecessary: function(list) {
		if (this.data == {}) return list;

		var result = {};
		for (let namespace in list) {
			let namespacedList = [];
			if (namespace in this.data) {
				for (let i=0, l=list[namespace].len; i<l; i++)
					if (!(list[namespace][i] in this.data[namespace]))
						namespacedList.push(list[namespace][i]);
			} else namespacedList = list[namespace];
			if (namespacedList.len) result[namespace] = namespacedList;
		}
		return result;
	},

	/**
	 * При создании модуля он подписывается на виджеты
	 * */
	depend: function(map) {
		for (var namespace in map) {
			if (!(namespace in this.data)) this.data[namespace] = {};
			let widgets = map[namespace];
			for (var i=0, l=widgets.len; i<l; i++) {
				let widget = widgets[i];
				if (widget in this.data[namespace]) this.data[namespace][widget]++;
				else {
					this.data[namespace][widget] = 1;
				}
			}
		}
	},

	/**
	 * При удалении модуля он отписывается от виджетов
	 * Если на виджет больше никто не подписан и виджеты не кэшируются, такой виджет будет удален
	 * */
	independ: function(map) {
		for (var namespace in map) {
			if (!(namespace in this.data)) continue;

			let widgets = map[namespace];
			for (var i=0, l=widgets.len; i<l; i++) {
				let widget = widgets[i];
				if (!(widget in this.data[namespace])) continue;

				this.data[namespace][widget]--;
				if (this.data[namespace][widget] == 0 && !this.cache) {
					delete this.data[namespace][widget];
					delete window[namespace][widget];
					if (window[namespace].lxEmpty) delete window[namespace];
				}
			}
		}
	}
};
