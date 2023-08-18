import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_html/style.dart';
import 'package:styled_widget/styled_widget.dart';

import '../../../../app/service/app_settings_service.dart';

final paymentInitialMembershipWidgetMembershipsWrapperContainer = (
  List<Widget> children,
) =>
    Column(
      children: children,
    ).padding(
      horizontal: 16,
      top: 6,
    );

final paymentInitialMembershipWidgetLinksWrapperContainer = (
  List<Widget> children,
) =>
    Column(
      children: children,
    ).padding(
      vertical: 16,
    );

final paymentInitialMembershipWidgetHtmlStyleContainer = {
  "li": Style(
    margin: EdgeInsets.only(
      bottom: 8,
    ),
    lineHeight: LineHeight.number(1.2),
    color: AppSettingsService.themeCommonTextColor,
  ),
  "p": Style(
    lineHeight: LineHeight.number(1.2),
    color: AppSettingsService.themeCommonTextColor,
  ),
  "div": Style(
    lineHeight: LineHeight.number(1.2),
    color: AppSettingsService.themeCommonTextColor,
  ),
};
