import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/widgets.dart';
import 'package:styled_widget/styled_widget.dart';

import '../../../../../../font_icons/sk_mobile_font_icons.dart';
import '../../../../../app/service/app_settings_service.dart';

final dashboardTinderActionToolbarWidgetWrapperContainer = (
  List<Widget> children,
) =>
    Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: children,
    ).padding(
      bottom: 10,
    );

final dashboardTinderActionToolbarWidgetSmallIconContainer = (
  IconData icon,
  Function clickCallback, {
  double iconSize = 15,
  double iconPaddingTop = 0.0,
}) =>
    Styled.widget(
      child: Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: AppSettingsService
              .themeCustomDashboardTinderActionToolbarWidgetSmallIconBackgroundColor,
        ),
        child: Icon(
          icon,
          size: iconSize,
          color: AppSettingsService.themeCommonHardcodedWhiteColor,
        ).padding(
          top: iconPaddingTop,
        ),
      ),
    )
        .gestures(
          onTap: () => clickCallback(),
        )
        .padding(horizontal: 6);

final dashboardTinderActionToolbarWidgetShowIconContainer = (
  Function clickCallback,
) =>
    dashboardTinderActionToolbarWidgetSmallIconContainer(
      SkMobileFont.theme_4_down,
      clickCallback,
      iconSize: 14,
      iconPaddingTop: 5,
    );

final dashboardTinderActionToolbarWidgetHideIconContainer = (
  Function clickCallback,
) =>
    dashboardTinderActionToolbarWidgetSmallIconContainer(
      SkMobileFont.theme_4_up,
      clickCallback,
      iconSize: 14,
    );

final dashboardTinderActionToolbarWidgetProfileIconContainer = (
  Function clickCallback,
) =>
    dashboardTinderActionToolbarWidgetSmallIconContainer(
      SkMobileFont.theme_4_dashboard,
      clickCallback,
      iconSize: 16,
    );

final dashboardTinderActionToolbarWidgetBigIconContainer = (
  IconData icon,
  Color backgroundColor,
  double sizeIcon,
  Function clickCallback, {
  double iconPaddingTop = 0,
  double iconPaddingBottom = 0,
}) =>
    Container(
      width: 52,
      height: 52,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: backgroundColor,
      ),
      child: Icon(
        icon,
        size: sizeIcon,
        color: AppSettingsService.themeCommonIconLightColor,
      ).padding(
        top: iconPaddingTop,
        bottom: iconPaddingBottom,
      ),
    )
        .gestures(
          onTap: () => clickCallback(),
        )
        .padding(
          horizontal: 6,
        );

final dashboardTinderActionToolbarWidgetDislikeIconContainer = (
  Function clickCallback,
) =>
    dashboardTinderActionToolbarWidgetBigIconContainer(
      SkMobileFont.theme_dislike,
      AppSettingsService
          .themeCustomDashboardTinderActionToolbarDislikeIconBackgroundColor,
      25,
      clickCallback,
      iconPaddingTop: 3,
    );

final dashboardTinderActionToolbarWidgetRewindIconContainer = (
  bool isActive,
  Function clickCallback,
) =>
    Opacity(
      opacity: isActive ? 1 : 0.5,
      child: dashboardTinderActionToolbarWidgetBigIconContainer(
        SkMobileFont.ic_rewind,
        AppSettingsService
            .themeCommonDashboardTinderActionToolbarWidgetRewindIconBackgroundColor,
        22,
        () => isActive ? clickCallback() : null,
      ),
    );

final dashboardTinderActionToolbarWidgetLikeIconContainer = (
  Function clickCallback,
) =>
    dashboardTinderActionToolbarWidgetBigIconContainer(
      SkMobileFont.ic_like,
      AppSettingsService.themeCommonProfileActionToolbarLikeIconBackgroundColor,
      25,
      clickCallback,
      iconPaddingTop: 3,
    );
