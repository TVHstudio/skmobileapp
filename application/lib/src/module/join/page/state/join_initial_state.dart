import 'package:mobx/mobx.dart';

import '../../../base/page/state/root_state.dart';
import '../../../base/page/widget/form/form_builder_widget.dart';
import '../../service/join_initial_service.dart';
import '../../service/model/join_initial_avatar_model.dart';

part 'join_initial_state.g.dart';

class JoinInitialState = _JoinInitialState with _$JoinInitialState;

abstract class _JoinInitialState with Store {
  final JoinInitialService joinInitialService;
  final RootState rootState;
  double rotate = 0;

  @observable
  bool isPageLoading = true;

  @observable
  bool isAvatarUploading = false;

  JoinInitialAvatarModel? _avatar;

  _JoinInitialState({
    required this.joinInitialService,
    required this.rootState,
  });

  @action
  Future<void> init(FormBuilderWidget formBuilder) async {
    formBuilder.registerFormElements(
      await joinInitialService.getFormElements(
        rootState.getSiteSetting('minPasswordLength', 0),
        rootState.getSiteSetting('maxPasswordLength', 0),
      ),
    );

    isPageLoading = false;
  }

  bool isAvatarHidden() => rootState.getSiteSetting('isAvatarHidden', false);

  bool isAvatarRequired() =>
      rootState.getSiteSetting('isAvatarRequired', false);

  bool isAvatarUploaded() {
    return _avatar != null;
  }

  void setAvatar(JoinInitialAvatarModel? avatar) {
    _avatar = avatar;
  }

  JoinInitialAvatarModel? getAvatar() {
    return _avatar;
  }

  setAvatarRotate(double value) {
    rotate = value;
  }
}
