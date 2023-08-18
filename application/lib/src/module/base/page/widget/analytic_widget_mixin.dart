import 'package:firebase_analytics/firebase_analytics.dart';
import 'package:get_it/get_it.dart';

mixin AnalyticWidgetMixin {
  void logWebPaymentFinished() {
    _getService().logEcommercePurchase();
  }

  void logWebAddPaymentInfo() {
    _getService().logAddPaymentInfo();
  }

  void logViewItem(
    String itemId,
    String itemName,
    String itemCategory,
  ) {
    _getService().logViewItem(
      itemId: itemId,
      itemName: itemName,
      itemCategory: itemCategory,
    );
  }

  void logViewList(String listName) {
    _getService().logViewItemList(itemCategory: listName);
  }

  void logJoin({String joinMethod = 'regular'}) {
    _getService().logSignUp(signUpMethod: joinMethod);
  }

  void logLogin({String loginMethod = 'regular'}) {
    _getService().logLogin(loginMethod: loginMethod);
  }

  FirebaseAnalytics _getService() => GetIt.instance.get<FirebaseAnalytics>();
}
