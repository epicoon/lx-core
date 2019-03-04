#lx:private;

#lx:use lx.PositioningStrategy as PositioningStrategy;

/**
 * Позволяет задавать правила выравнивания для групп элементов
 * Можно задать выравнивание по умолчанию — для всех элементов, на которые не распространяются другие правила
 * Можно задать правило по ключу — все элементы с таким ключом формируют группу, которая выравнивается как единое целое
 * Можно задать правило для конкретных элементов, они так же сформируют группу
 * */
class AlignPositioningStrategy extends PositioningStrategy #lx:namespace lx {
	constructor(owner, config) {
		super(owner);

		this.counter = 0;
		this.defaultRule = null;  // Правило по умолчанию
		this.rules = [];        // массив правил
		this.keysToRules = [];  // массив ключей элементов, указывающих на ключи соответствующих им правил

		if (config) this.addRule(config);
	}

	unpackProcess(config) {
		this.counter = config.c;

		if (config.dr) AlignRuleDefault.unpack(this, config.dr);

		if (config.r) {
			var r = config.r.split('|');
			r.each((info)=> AlignRule.unpack(this, info));
		}
	}

	/**
	 * Аргументы в двух видах:
	 * 1. (hor, vert, subject)
	 * 2. ({
	 *       horizontal: lx.CENTER | lx.LEFT | lx.RIGHT,  // обязательное
	 *       vertical: lx.MIDDLE | lx.TOP | lx.BOTTOM,    // обязательное
	 *       subject: subject,  // см. ниже
	 *       direction: lx.VERTICAL | default(lx.HORIZONTAL),
	 *       ...
	 *       config для IndentData
	 *    })
	 * subject - может быть:
	 * 1. Ключ
	 * 2. Элемент
	 * 3. Массив ключей и/или элементов
	 * 4. Может быть пустым, тогда создается правило "по умолчанию" - для всех дочерних элементов, без явной привязки к какому-то правилу
	 * */
	addRule(config, vert, subject) {
		if (vert !== undefined) {
			this.addRule({
				horizontal: config,
				vertical: vert,
				subject
			});
			return;
		}

		// Если субъекты не переданы, значит задается правило по умолчанию
		if (!config.subject) {
			this.defaultRule = new AlignRuleDefault(this, config);
			this.defaultRule.actualize();
			return this.defaultRule;
		}

		// Если пришедшие ключи или элементы уже фигурируют в правилах - почистим правила
		lx.Collection.cast(config.subject).each((a)=> {
			var rule = null;
			if (a.isString) {
				if (a in this.keysToRules) rule = this.rules[this.keysToRules[a]];
			} else if (a.ruleId) rule = this.rules[a.ruleId];
			if (rule) rule.remove(a);
		});

		// Создаем правило
		var ruleId = this.genId();
		this.rules[ruleId] = new AlignRule(this, config, ruleId);
		this.rules[ruleId].actualize();

		// Актуализируем дефолтное правило, т.к. у него часть элементов сейчас была забрана
		if (this.defaultRule) this.defaultRule.actualize();
		return this.rules[ruleId];
	}

	actualizeProcess(info) {
		// без параметров вызывается общая актуализация
		if (!info) {
			// Актуализация дефолтного правила, если есть
			if (this.defaultRule) this.defaultRule.actualize();
			// Для элементов, которые не относятся ни к одному правилу - чтобы стандартная логика работала
			else this.owner.getChildren((a)=>!a.ruleId).each((a)=> a.trigger('resize'));
			// Актуализация самих правил
			this.rules.each((rule)=> rule.actualize());
			return;
		}

		var ids = [],
			defaultChanged = false;

		// выберем id-правил для изменившегося элемента
		if (info.changed) {
			if (info.changed.ruleId) ids.push(info.changed.ruleId);
			else defaultChanged = true;
		}

		// выберем id-правил для удаленных элементов и выкинем сами элементы из правил
		if (info.deleted) {
			info.deleted.each((a)=> {
				if (!a.ruleId) {
					defaultChanged = true;
					return;
				}
				this.rules[a.ruleId].remove(a);
				if (a.ruleId in this.rules) ids.pushUnique(a.ruleId);
			});
		}

		ids.each((a)=> this.rules[a].actualize());
		if (defaultChanged && this.defaultRule) this.defaultRule.actualize();
	}

	allocate(elem, config) {
		var ruleId = null;
		if (elem.key in this.keysToRules) ruleId = this.keysToRules[elem.key];

		if (!ruleId && !this.defaultRule) {
			super.allocate(elem, config);
			return;
		}

		var geom = this.geomFromConfig(config);
		this.setParam(elem, lx.WIDTH, geom.w || 0);
		this.setParam(elem, lx.HEIGHT, geom.h || 0);
		elem.trigger('resize');

		elem.ruleId = ruleId;
		this.rule(ruleId).actualize();
	}

	rule(id) {
		if (!id) return this.defaultRule;
		return this.rules[id];
	}

	reset() {
		this.owner.getChildren().each((a)=> delete a.ruleId);
		this.counter = 0;
		this.rules = [];
		this.keysToRules = [];
	}

	tryReposition(elem, param, val) {
		if (!(elem.key in this.keysToRules) && !this.defaultRule)
			return super.tryReposition(elem, param, val);

		// можно менять размеры, но нельзя менять положение
		if (param == lx.LEFT || param == lx.RIGHT || param == lx.TOP || param == lx.BOTTOM) return false;

		this.setParam(elem, param, val);
		elem.trigger('resize');
		this.rule(elem.ruleId).actualize();
		return true;
	}

	reactForAutoresize(elem) {
		this.rule(elem.ruleId).actualize();
	}

	genId() {
		return 'r' + lx.Math.decChangeNotation(this.counter++, 62);
	}
}


class AlignRuleAbstract {
	constructor(owner, config) {
		this.owner = owner;

		this.h = config.horizontal;
		this.v = config.vertical;
		this.dir = config.direction || lx.HORIZONTAL;

		var data = lx.IndentData.createOrNull(config);
		if (data) this.indents = data;
	}

	getIndents() {
		if (this.indents) return this.indents.get(this.owner.owner);
		return this.owner.getIndents();
	}

	getElements() {}

	actualize() {
		var ps = this.owner,
			owner = ps.owner,
			h = this.h,
			v = this.v,
			direction = this.dir,
			indents = this.getIndents(),
			els = this.getElements();

		var temp = ps.defaultFormat;
		ps.defaultFormat = PositioningStrategy.FORMAT_PX;

		var hCenter =(justify)=> {
			var w = owner.getInnerSize(lx.WIDTH);
			if (direction == lx.HORIZONTAL) {
				var x = lx.Math.roundToZero((w - els.sum('width', 'px') - indents.stepX*(els.len - 1)) * 0.5);
				els.each((a)=> {
					a.geomPriorityH(lx.LEFT, lx.WIDTH);
					ps.setParam(a, lx.LEFT, x);
					x += indents.stepX + a.width('px');
					a.style('textAlign', justify ? 'justify' : 'center');
				});
			} else {
				els.each((a)=> {
					a.geomPriorityH(lx.LEFT, lx.WIDTH);
					ps.setParam(a, lx.LEFT, lx.Math.roundToZero((w - a.width('px')) * 0.5));
				});
			}
		};


		switch (+h) {
			case lx.LEFT:		
				if (direction == lx.HORIZONTAL) {
					var x = indents.paddingLeft;
					els.each((a)=> {
						a.geomPriorityH(lx.LEFT, lx.WIDTH);
						ps.setParam(a, lx.LEFT, x);
						x += indents.stepX + a.width('px');
					});
				} else {
					els.each((a)=> { a.geomPriorityH(lx.LEFT, lx.WIDTH); ps.setParam(a, lx.LEFT, indents.paddingLeft); });
				}
			break;
			case lx.JUSTIFY: hCenter(true); break;
			case lx.CENTER: hCenter(false); break;
			case lx.RIGHT:
				if (direction == lx.HORIZONTAL) {
					var x = indents.paddingRight;
					els.eachRevert((a)=> {
						a.geomPriorityH(lx.RIGHT, lx.WIDTH);
						ps.setParam(a, lx.RIGHT, x);
						x += indents.stepX + a.width('px');
						a.style('textAlign', 'right');
					});
				} else {
					els.each((a)=> { a.geomPriorityH(lx.RIGHT, lx.WIDTH); ps.setParam(a, lx.RIGHT, indents.paddingRight); });
				}
			break;
		}

		switch (+v) {
			case lx.TOP:
				if (direction == lx.HORIZONTAL) {
					els.each((a)=> { a.geomPriorityV(lx.TOP, lx.HEIGHT); ps.setParam(a, lx.TOP, indents.paddingTop); });
				} else {
					var y = indents.paddingTop;
					els.each((a)=> {
						a.geomPriorityV(lx.TOP, lx.HEIGHT);
						ps.setParam(a, lx.TOP, y);
						y += indents.stepY + a.height('px');
					});					
				}
			break;
			case lx.MIDDLE:
				var h = owner.getInnerSize(lx.HEIGHT);
				if (direction == lx.HORIZONTAL) {
					els.each((a)=> {
						a.geomPriorityV(lx.TOP, lx.HEIGHT);
						ps.setParam(a, lx.TOP, lx.Math.roundToZero((h - a.height('px')) * 0.5));
					});
				} else {
					var y = lx.Math.roundToZero((h - els.sum('height', 'px') - indents.stepY*(els.len - 1)) * 0.5);
					els.each((a)=> {
						a.geomPriorityV(lx.TOP, lx.HEIGHT);
						ps.setParam(a, lx.TOP, y);
						y += indents.stepY + a.height('px');
					});
				}
			break;
			case lx.BOTTOM:
				if (direction == lx.HORIZONTAL) {
					els.each((a)=> { a.geomPriorityV(lx.BOTTOM, lx.HEIGHT); ps.setParam(a, lx.BOTTOM, indents.paddingBottom); });
				} else {
					var y = indents.paddingBottom;
					els.eachRevert((a)=> {
						a.geomPriorityV(lx.BOTTOM, lx.HEIGHT);
						ps.setParam(a, lx.BOTTOM, y);
						y += indents.stepY + a.height('px');
					});
				}
			break;
		}
		ps.defaultFormat = temp;
	}
}

class AlignRuleDefault extends AlignRuleAbstract {
	getElements() {
		return this.owner.owner.getChildren((a)=>!a.ruleId);
	}

	static unpack(owner, info) {
		info = info.split('+');

		var config = {
				direction: info[0],
				horizontal: info[1],
				vertical: info[2]
			},
			rule = new this(owner, config);

		if (info.len > 3) {
			rule.indents = lx.IndentData.unpackOrNull(info[3].split('=')[1]);
		}
		owner.defaultRule = rule;
	}
}

class AlignRule extends AlignRuleAbstract {
	constructor(owner, config, id) {
		super(owner, config);

		this.list = [];
		this.id = id;
		lx.Collection.cast(config.subject).each((a)=> this.addProcess(a));
	}

	static unpack(owner, info) {
		info = info.split('+');

		var id = info[3],
			list = info[4].split(','),
			subject = [];
		list.each((a, i)=> {
			if (a[0] == '=') subject.push(owner.owner.get(a.substr(1)));
			else {
				subject.push(a);
				owner.keysToRules[a] = id;
			}
		});

		var config = {
				direction: info[0],
				horizontal: info[1],
				vertical: info[2],
				subject
			},
			rule = new this(owner, config, id);

		if (info.len > 5) {
			rule.indents = lx.IndentData.unpackOrNull(info[5].split('=')[1]);
		}
		owner.rules[id] = rule;
	}

	add(elem) {
		this.addProcess(elem);
		this.actualize();
	}

	addProcess(elem) {
		this.list.push(elem);
		if (elem.isString) {
			this.owner.keysToRules[elem] = this.id;
			if (!this.owner.owner.contain(elem)) return;
			lx.Collection.cast(this.owner.owner.get(elem)).each((b)=> {
				b.ruleId = this.id;
			});
		} else elem.ruleId = this.id;
	}

	remove(el) {
		if (el.isArray) {
			el.each((a)=> this.remove(a));
			return;
		}

		if (el.isString) {
			delete this.owner.keysToRules[el];
			var els = this.owner.owner.get(el);
			if (els) {
				els = lx.Collection.cast(els);
				els.each((a)=> { delete a.ruleId; });
			}
		} else delete el.ruleId;

		this.list.remove(el);
		if (this.list.lxEmpty) delete this.owner.rules[this.id];
		else this.actualize();

		if (this.owner.defaultRule)
			this.owner.defaultRule.actualize();
	}

	getElements() {
		var result = new lx.Collection();
		this.list.each((elem)=> {
			if (elem.isString) {
				if (!this.owner.owner.contain(elem)) return;
				var elems = this.owner.owner.get(elem);
				lx.Collection.cast(elems).each((a)=> {
					if (a.ruleId == this.id) result.add(a);
				});
			}
			else result.add(elem);
		});
		return result;
	}
}
