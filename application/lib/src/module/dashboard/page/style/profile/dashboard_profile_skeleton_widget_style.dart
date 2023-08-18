import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:styled_widget/styled_widget.dart';

import '../../../../../app/service/app_settings_service.dart';
import '../../../../base/page/widget/skeleton/circle_skeleton_element_widget.dart';

final dashboardProfileSkeletonWidgetWrapperContainer =
    (Widget child) => Styled.widget(
          child: child,
        ).backgroundColor(AppSettingsService.themeCommonScaffoldLightColor);

final dashboardProfileSkeletonWidgetAvatarContainer = (
  BuildContext context,
) =>
    SizedBox(
      height: MediaQuery.of(context).size.height * 0.26,
      width: MediaQuery.of(context).size.height * 0.26,
      child: CircleSkeletonElementWidget(),
    ).padding(
      top: 24,
      bottom: 15,
    );

final dashboardProfileSkeletonWidgetButtonsWrapperContainer = (
  BuildContext context,
  List<Widget> children,
) =>
    Row(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: children,
    ).padding(
      vertical: 30,
    );
