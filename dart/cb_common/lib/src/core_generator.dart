part of core;

abstract class GeneratorDependable<K> {

  Stream<K> get onAdd;

  Stream<K> get onUpdate;

  Stream<K> get onRemove;


}

abstract class PairGeneratorDependable<K, V> implements GeneratorDependable<Pair<K, V>> {
  GeneratorDependable<K> get firstOfPairDependable => new FirstOfPairGeneratorDependableTransformer<K, V>(this);

  GeneratorDependable<V> get secondOfPairDependable => new SecondOfPairGeneratorDependableTransformer<K, V>(this);

}


class FirstOfPairGeneratorDependableTransformer<K, V> extends GeneratorDependableTransformer<Pair<K, V>, K> {

  FirstOfPairGeneratorDependableTransformer(GeneratorDependable<Pair<K, V>> dependable) : super(dependable, (Pair<K, V> p) => p.k);

}

class SecondOfPairGeneratorDependableTransformer<K, V> extends GeneratorDependableTransformer<Pair<K, V>, V> {

  SecondOfPairGeneratorDependableTransformer(GeneratorDependable<Pair<K, V>> dependable) : super(dependable, (Pair<K, V> p) => p.v);

}

class GeneratorDependableTransformer<K, T> implements GeneratorDependable<T> {

  final GeneratorDependable<K> dependable;
  final Function transformer;

  GeneratorDependableTransformer(GeneratorDependable<K> dependable, T transformer(K)): this.dependable = dependable, this.transformer = transformer;

  Stream<T> get onAdd => dependable.onAdd.map(transformer);

  Stream<T> get onUpdate => dependable.onUpdate.map(transformer);

  Stream<T> get onRemove => dependable.onRemove.map(transformer);


}


class Generator<K, V> extends PairGeneratorDependable<K, V> {

  Map<K, V> _cache;
  Function _generator;

  List<Function> _updaters = <Function>[];

  List<Function> _handlers = <Function>[];

  final StreamController<Pair<K, V>>
  _onBeforeAddController = new StreamController<Pair<K, V>>.broadcast(),
  _onBeforeRemoveController = new StreamController<Pair<K, V>>.broadcast(),
  _onEmptyController = new StreamController<Pair<K, V>>.broadcast(),
  _onNotEmptyController = new StreamController<Pair<K, V>>.broadcast(),
  _onAddController = new StreamController<Pair<K, V>>.broadcast(),
  _onUpdateController = new StreamController<Pair<K, V>>.broadcast(),
  _onRemoveController = new StreamController<Pair<K, V>>.broadcast();


  Generator(V generator(K), Map<K, V> cache) : _cache = cache, _generator = generator;

  void addUpdater(void updater(K, V)) {
    _handlers.insert(_updaters.length, updater);
    _updaters.add(updater);
  }

  void addHandler(void handler(K, V)) {
    _cache.forEach(handler);
    _handlers.add(handler);
  }


  Stream<Pair<K, V>> get onEmpty => onRemove.where((_) => size == 0);

  Stream<Pair<K, V>> get onNotEmpty => onAdd.where((_) => size == 1);

  Stream<Pair<K, V>> get onAdd => _onAddController.stream;

  Stream<Pair<K, V>> get onBeforeAdd => _onBeforeAddController.stream;

  Stream<Pair<K, V>> get onUpdate => _onUpdateController.stream;

  Stream<Pair<K, V>> get onRemove => _onRemoveController.stream;

  Stream<Pair<K, V>> get onBeforeRemove => _onBeforeRemoveController.stream;

  int get size => _cache.length;

  void update(K k) {
    if (!contains(k)) {
      return;
    }
    var v = this[k];
    _callUpdaters(k, v);
    _onUpdateController.add(new Pair(k, v));

  }

  void _callUpdaters(K k, V v) => _callFunctions(_updaters, k, v);

  void _callHandlers(K k, V v) => _callFunctions(_handlers, k, v);

  void _callFunctions(List<Function> fs, K k, V v) => fs.forEach((Function f) => f(k, v));

  V operator [](K k){
    add(k);
    return _cache[k];
  }

  void add(K k) {
    if (contains(k)) {
      return;
    }
    var v = _cache[k] = _generator(k);
    _callHandlers(k, v);
    _onBeforeAddController.add(new Pair<K, V>(k, v));
    _onAddController.add(new Pair<K, V>(k, v));
  }

  void remove(K k) {
    if (!contains(k)) {
      return;
    }
    var v = this[k];
    _cache.remove(k);
    _onBeforeRemoveController.add(new Pair<K, V>(k, v));
    _onRemoveController.add(new Pair<K, V>(k, v));
  }


  bool contains(K k) => _cache.containsKey(k);


  void dependsOn(GeneratorDependable<K> generator, {bool whenAdd(K), bool whenRemove(K), bool whenUpdate(K)}) {
    var action = (bool when(K), void f(K))=>(K k){
      if(when != null && !when(k)){
        return;
      }
      f(k);
    };

    generator.onAdd.listen(action(whenAdd, add));
    generator.onRemove.listen(action(whenRemove, remove));
    generator.onUpdate.listen(action(whenUpdate, update));

  }


}
