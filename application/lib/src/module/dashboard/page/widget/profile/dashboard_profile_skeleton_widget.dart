import 'package:flutter/material.dart';

import '../../../../base/page/widget/skeleton/bar_skeleton_element_widget.dart';
import '../../style/profile/dashboard_profile_skeleton_widget_style.dart';

class DashboardProfileSkeletonWidget extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return dashboardProfileSkeletonWidgetWrapperContainer(
      Column(
        children: [
          dashboardProfileSkeletonWidgetAvatarContainer(
            context,
          ),
          BarSkeletonElementWidget(
            height: 10,
            width: 80,
          ),
          BarSkeletonElementWidget(
            height: 10,
            width: 80,
          ),
          dashboardProfileSkeletonWidgetButtonsWrapperContainer(
            context,
            [
              BarSkeletonElementWidget(
                height: 42,
                width: 130,
                borderRadius: 25,
                paddingLeft: 7,
                paddingRight: 7,
              ),
              BarSkeletonElementWidget(
                height: 42,
                width: 130,
                borderRadius: 25,
                paddingLeft: 7,
                paddingRight: 7,
              ),
            ],
          ),
          BarSkeletonElementWidget(
            height: 10,
            width: 175,
            paddingBottom: 25,
          ),
          BarSkeletonElementWidget(
            height: 10,
            width: 175,
            paddingBottom: 25,
          ),
          BarSkeletonElementWidget(
            height: 10,
            width: 175,
            paddingBottom: 25,
          ),
        ],
      ),
    );
  }
}
