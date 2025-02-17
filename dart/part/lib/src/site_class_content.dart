part of site_classes;

abstract class Content {
  final String id;

  Content(this.id);

  Future<List<DateTime>> get changeTimes;

  Future<Revision> operator [](DateTime time);

  Future<Revision> addContent(String content);

  Future<List<Revision>> listRevisions({DateTime from:null, DateTime to:null});

  Stream<Revision> get onAddContent;

  bool get hasDraft;

  Revision get draft;

  void set draft(Revision);

  void clearDraft();

  Stream<bool> get onHasDraftChange;

}


abstract class AJAXContentFunctionGenerator {

  final String contentLibrary, id, content_id;

  AJAXContentFunctionGenerator(this.contentLibrary, this.id, this.content_id);

  String generateListContentFunction({int from:null, int to: null, bool includeContent:false}) => contentLibrary + ".listContentHistory($from, $to, ${!includeContent})";

  String generateContentAtTimeFunction(num time) => contentLibrary + ".getContentAt($time)";

  Map generateAddContentFunction(String content) {
    var formData = new FormData();
    formData.append("content", content);
    return {
        "function":contentLibrary + ".addContent(POST['content'])", "formdata":formData
    };
  }

}


class JSONContentPageFunctionGenerator extends AJAXContentFunctionGenerator {
  final Page page;

  JSONContentPageFunctionGenerator(Page page, String id) :
  super(
      "PageOrder.getPage(${quoteString(page.id)}).getContent(${quoteString(id)})",
      id,
      "PageContent.${page.id}.$id"), this.page = page;

}

class JSONContentSiteFunctionGenerator extends AJAXContentFunctionGenerator {

  JSONContentSiteFunctionGenerator(String id) : super(
      "Site.getContent(${quoteString(id)})",
      id,
      "SiteContent.$id");


}

class AJAXContent extends Content {

  StreamController<bool> onHasDraftChangeController = new StreamController.broadcast();

  final AJAXContentFunctionGenerator contentStrategy;

  AJAXContent(AJAXContentFunctionGenerator contentStrategy): super(contentStrategy.id), this.contentStrategy = contentStrategy;

  AJAXContent.page(Page page, String id) : this(new JSONContentPageFunctionGenerator(page, id));

  AJAXContent.site(String id) : this(new JSONContentSiteFunctionGenerator(id));

  Map<DateTime, Revision> _revisions = new Map<DateTime, Revision>();

  StreamController<Revision> _streamController = new StreamController<Revision>();
  Stream<Revision> _stream;

  Future<List<DateTime>> get changeTimes {
    var completer = new Completer<List<DateTime>>();
    ajaxClient.callFunctionString(contentStrategy.generateListContentFunction()).then((Response response) {
      if (response.type == Response.RESPONSE_TYPE_SUCCESS) {
        List<String> payload = response.payload == null ? [] : response.payload;
        completer.complete(payload.map((String timeString) => new DateTime.fromMillisecondsSinceEpoch(int.parse(timeString) * 1000)).toList(growable:false));
      } else {
        completer.completeError(new Exception("Could not list times"));
      }
    });
    return completer.future;
  }

  Future<Revision> operator [](DateTime time){
    var completer = new Completer<Revision>();

    ajaxClient.callFunctionString(contentStrategy.generateContentAtTimeFunction(time.millisecond ~/ 1000)).then((Response response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.completeError(new Exception("Could not get content at time"));
        return;
      }
      if (response.payload == null) {
        completer.complete(null);
        return;
      }

      completer.complete(_generateRevision(new DateTime.fromMillisecondsSinceEpoch(int.parse(response.payload['time']) * 1000), response.payload['content']));
    });
    return completer.future;
  }

  Future<Revision> addContent(String content) {
    var completer = new Completer<Revision>();
    var c = contentStrategy.generateAddContentFunction(content);
    ajaxClient.callFunctionString(c["function"], form_data:c["formdata"]).then((Response response) {
      if (response.type != Response.RESPONSE_TYPE_SUCCESS) {
        completer.completeError(new Exception("Could not add content"));
        return;
      }
      var r;
      completer.complete(r = _generateRevision(new DateTime.fromMillisecondsSinceEpoch((response.payload is String ? int.parse(response.payload) : response.payload) * 1000), content));
      _streamController.add(r);
    });
    return completer.future;
  }

  Future<List<Revision>> listRevisions({DateTime from:null, DateTime to:null}) {
    var completer = new Completer<List<Revision>>();
    from = from == null ? new DateTime.fromMillisecondsSinceEpoch(0) : from;
    to = to == null ? new DateTime.now() : to;

    var fromm = from.millisecondsSinceEpoch ~/ 1000, too = to.millisecondsSinceEpoch ~/ 1000;
    ajaxClient.callFunctionString(contentStrategy.generateListContentFunction(from:fromm, to:too, includeContent:true)).then((Response response) {
      if (response.type == Response.RESPONSE_TYPE_SUCCESS) {
        List<Map<String, dynamic>> payload = response.payload == null ? [] : response.payload;
        completer.complete(payload.map((Map<String, dynamic> m) => _generateRevision(new DateTime.fromMillisecondsSinceEpoch(int.parse(m['time']) * 1000), m['content'])).toList(growable:false));

      } else {
        completer.completeError(new Exception("Could not list revisions"));
      }
    });

    return completer.future;
  }

  Revision _generateRevision(DateTime time, String content) => _revisions.putIfAbsent(time, () => new Revision(time, content));


  Stream<Revision> get onAddContent => _stream == null ? _stream = _streamController.stream.asBroadcastStream() : _stream;


  bool get hasDraft => window.localStorage.containsKey(contentStrategy.content_id) && window.localStorage.containsKey(contentStrategy.content_id + ".time");

  Revision get draft => hasDraft ? new Revision(new DateTime.fromMillisecondsSinceEpoch(int.parse(window.localStorage[contentStrategy.content_id + ".time"])), window.localStorage[contentStrategy.content_id]) : null;

  void set draft(Revision revision) {
    window
      ..localStorage[contentStrategy.content_id + ".time"] = revision.time.millisecondsSinceEpoch.toString()
      ..localStorage[contentStrategy.content_id] = revision.content;
    onHasDraftChangeController.add(hasDraft);
  }

  Stream<bool> get onHasDraftChange => onHasDraftChangeController.stream;

  void clearDraft() {
    window.localStorage
      ..remove(contentStrategy.content_id + ".time")
      ..remove(contentStrategy.content_id);
    onHasDraftChangeController.add(hasDraft);
  }

}
