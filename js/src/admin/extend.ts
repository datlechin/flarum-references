import Extend from 'flarum/common/extenders';

import { SETTING, settingKey, trans, transText } from './config';
import ReferencesSettingsPage from './components/ReferencesSettingsPage';

export default [
  new Extend.Admin()
    // `.page()` replaces the renderer that draws registered settings, so
    // anything added through `.setting()` here would never reach the screen.
    .page(ReferencesSettingsPage)

    // A page that draws its own fields has to list them for the admin search.
    .generalIndexItems('settings', () =>
      Object.values(SETTING).map((name) => ({
        id: settingKey(name),
        label: transText(`settings.${name}_label`),
        help: transText(`settings.${name}_help`),
      }))
    )

    .permission(
      () => ({
        icon: 'fas fa-link',
        label: trans('permissions.manage_references'),
        permission: 'datlechin-references.manageReferences',
      }),
      'moderate',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-unlink',
        label: trans('permissions.view_broken_report'),
        permission: 'datlechin-references.viewBrokenReport',
      }),
      'moderate',
      90
    )
    .permission(
      () => ({
        icon: 'fas fa-chart-line',
        label: trans('permissions.view_analytics'),
        permission: 'datlechin-references.viewAnalytics',
      }),
      'moderate',
      85
    ),
];
