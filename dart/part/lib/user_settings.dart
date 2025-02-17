library user_settings;

import "dart:html";
import "dart:math" as Math;
import "dart:async";

import 'initializers.dart';
import 'site_classes.dart';
import 'json.dart';
import 'core.dart' as core;
import 'elements.dart';
import 'function_string_parser.dart' as FS;

import 'pcre_syntax_checker.dart' as PCRE;


part 'src/user_settings_page_order.dart';
part 'src/user_settings_user_library.dart';
part 'src/user_settings_decoration.dart';
part 'src/user_settings_page_initializers.dart';
part 'src/user_settings_pages_initializers.dart';
part 'src/user_settings_user_initializers.dart';
part 'src/user_settings_users_initializers.dart';
part 'src/user_settings_log_initializers.dart';
part 'src/user_settings_update_initializers.dart';

bool get pageOrderAvailable => querySelector("#ActivePageList") != null && querySelector("#InactivePageList") != null;

bool get userLibraryAvailable => pageOrderAvailable && querySelector('#UserList') != null;

bool get mailDomainLibraryAvailable => userLibraryAvailable && querySelector('#UserSettingsContent #UserSettingsEditMailDomainList') != null;

PageOrder get pageOrder => pageOrderAvailable ? new UserSettingsJSONPageOrder() : new OnePageUserSettingsPageOrder();


UserLibrary get userLibrary => userLibraryAvailable ? new UserSettingsJSONUserLibrary() : new NullUserLibrary();



String _errorMessage(int error_code) {
  switch (error_code) {
    case core.Response.ERROR_CODE_PAGE_NOT_FOUND:
      return "Siden blev ikke fundet";
    case core.Response.ERROR_CODE_INVALID_PAGE_ID:
      return "Ugyldigt side id";
    case core.Response.ERROR_CODE_INVALID_PAGE_ALIAS:
      return "Ugyldigt side alias";
    case core.Response.ERROR_CODE_UNAUTHORIZED:
      return "Du har ikke de nødvendige rettigheder";
    case core.Response.ERROR_CODE_INVALID_MAIL:
      return "Ugyldig mail-adresse";
    case core.Response.ERROR_CODE_INVALID_USER_NAME:
      return "Ugyldig brugernavn";
    case core.Response.ERROR_CODE_WRONG_PASSWORD:
      return "Forkert kodeord";
    case core.Response.ERROR_CODE_INVALID_PASSWORD:
      return "Ugyldigt kodeord";
    case core.Response.ERROR_CODE_COULD_NOT_PARSE_RESPONSE:
      return "Ugyldigt svar fra server";
    case core.Response.ERROR_CODE_NO_CONNECTION:
      return "Ingen forbindelse til serveren";
    default:
      return "Ukendt fejl";
  }
}

class UserSettingsInitializer extends core.Initializer {

  core.InitializerLibrary _initLib;

  UserSettingsInitializer(this._initLib);


  bool get canBeSetUp => querySelector('#UserSettingsContainer') != null && pageOrderAvailable && userLibraryAvailable;

  void setUp() {
    var client = new AJAXJSONClient();
    var order = pageOrder, userLib = userLibrary;

    FS.register
      ..addType(PageOrder, pageOrder)
      ..addType(Page, pageOrder.currentPage)
      ..addType(UserLibrary, userLibrary)
      ..addType(User, userLibrary.userLoggedIn);
    if (mailDomainLibraryAvailable) {
      FS.register
        ..addType(MailDomain)
        ..addType(MailAddressLibrary)
        ..addType(MailAddress)
        ..addType(MailMailbox);

    }

    _initLib.registerInitializer(new TitleURLUpdateInitializer(order, client));
    _initLib.registerInitializer(new UserSettingsDecorationInitializer());
    _initLib.registerInitializer(new UserSettingsPageListsInitializer());
    _initLib.registerInitializer(new UserSettingsEditPageFormInitializer(order));
    _initLib.registerInitializer(new UserSettingsChangeUserInfoFormInitializer(userLib));
    _initLib.registerInitializer(new UserSettingsAddPageFormInitializer(order));
    _initLib.registerInitializer(new UserSettingsUserListInitializer(userLib));
    _initLib.registerInitializer(new UserSettingsAddUserFormInitializer(userLib));
    _initLib.registerInitializer(new UserSettingsPageUserListFormInitializer(userLib, order));
    _initLib.registerInitializer(new UserSettingsUpdateSiteInitializer());
    _initLib.registerInitializer(new UserSettingsLoggerInitializer());

    /* SET UP LOGIN USER MESSAGE*/
    var loginUserMessage = querySelector('#LoginUserMessage');
    var i = loginUserMessage.querySelector('i');
    userLibrary.userLoggedIn.onChange.listen((User u) {
      i.text = u.username;
    });

  }

}


class UserSettingsDecorationInitializer extends core.Initializer {
  var
  _expandLink = querySelector("#UserSettingsExpandLink"),
  _contractLink = querySelector("#UserSettingsContractLink"),
  _container = querySelector("#UserSettingsContainer"),
  _slideElement = querySelector("#UserSettingsContent > ul"),
  _slideMenuList = querySelector("#UserSettingsMenu > ul");


  bool get canBeSetUp => _expandLink != null && _contractLink != null && _container != null && _slideElement != null && _slideMenuList != null;

  void setUp() {
    var expander = new UserSettingsExpandDecoration();
    _expandLink.onClick.listen((_) {
      expander.expand();
      core.escQueue.add(() {
        if (!expander._expanded) {
          return false;
        }
        var f = _container.querySelector(':focus');
        if (f != null) {
          f.blur();
        }
        expander.contract();
        return true;
      });
    });
    _contractLink.onClick.listen((_) => expander.contract());

    _slideElement.style.width = "${_slideElement.children.length*100}%";

    _slideMenuList.onClick.listen((Event e){
      var t = e.target;
      if(!_slideMenuList.children.contains(t)){
        return;
      }
      _slideElement.style.marginLeft = "${-100*_slideMenuList.children.indexOf(t)}%";
      _slideMenuList.querySelectorAll('li.active').forEach((LIElement li){
        li.classes.remove('active');
      });
      t.classes.add('active');
    });


  }
}