import app from 'flarum/admin/app';
import type { SaveSubmitEvent } from 'flarum/admin/components/AdminPage';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import FieldSet from 'flarum/common/components/FieldSet';
import Form from 'flarum/common/components/Form';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import { NUMBER_BOUNDS, SETTING, settingKey, trans } from '../config';
import ReferenceAnalytics from './ReferenceAnalytics';
import BrokenReferences from './BrokenReferences';

type FieldOptions = { type: string; [attr: string]: unknown };

export default class ReferencesSettingsPage extends ExtensionPage {
  content() {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <Form>
            {this.settingSections().toArray()}
            <div className="Form-group Form-controls">
              {this.submitButton()}
              {this.resetButton(
                undefined,
                app.translator.trans(
                  'core.admin.extension.reset_settings.title_extension',
                  { extensionTitle: this.extension.extra['flarum-extension'].title },
                  true
                ),
                this.extension.id
              )}
            </div>
          </Form>
        </div>
      </div>
    );
  }

  // `sections()` sorts descending and seeds `content` at 0, so the form has to
  // be raised above the panels rather than the panels lowered below it.
  sections(vnode: Mithril.VnodeDOM<any, this>) {
    const items = super.sections(vnode);

    items.setPriority('content', 10);
    items.add('analytics', <ReferenceAnalytics />, 5);
    items.add('broken', <BrokenReferences />, 4);

    return items;
  }

  saveSettings(e: SaveSubmitEvent) {
    Object.keys(NUMBER_BOUNDS).forEach((name) => this.clampNumber(name));

    return super.saveSettings(e);
  }

  // Nothing enforces `min` on a number input, and an emptied one saves an
  // empty row that the server then clamps on every read anyway.
  protected clampNumber(name: string): void {
    const { min, fallback } = NUMBER_BOUNDS[name];
    const setting = this.setting(settingKey(name));
    const raw = String(setting() ?? '').trim();
    const clamped = raw === '' || !Number.isFinite(Number(raw)) ? fallback : Math.max(min, Math.trunc(Number(raw)));

    if (String(clamped) !== raw) {
      setting(String(clamped));
    }
  }

  protected field(name: string, options: FieldOptions): Mithril.Children {
    const key = settingKey(name);
    const label = trans(`settings.${name}_label`);
    const bounds = NUMBER_BOUNDS[name];

    this.setting(key, '', label);

    return this.buildSettingComponent({
      setting: key,
      label,
      help: trans(`settings.${name}_help`),
      ...(bounds ? { min: bounds.min, onblur: () => this.clampNumber(name) } : {}),
      ...options,
    });
  }

  protected section(name: string, fields: Mithril.Children[]): Mithril.Children {
    return (
      <FieldSet className="FieldSet--form" label={trans(`sections.${name}_label`)} description={trans(`sections.${name}_description`)}>
        {fields}
      </FieldSet>
    );
  }

  protected settingSections(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'behaviour',
      this.section('behaviour', [
        this.field(SETTING.enabled, { type: 'boolean' }),
        this.field(SETTING.extractUrlReferences, { type: 'boolean' }),
        this.field(SETTING.extractShortReferences, { type: 'boolean' }),
        this.field(SETTING.extractMentionReferences, { type: 'boolean' }),
      ]),
      40
    );

    items.add(
      'display',
      this.section('display', [
        this.field(SETTING.maxPreview, { type: 'number' }),
        this.field(SETTING.eventPostEnabled, { type: 'boolean' }),
        this.field(SETTING.notifyFollowers, { type: 'boolean' }),
      ]),
      30
    );

    items.add(
      'discovery',
      this.section('discovery', [
        this.field(SETTING.relatedDiscussionsEnabled, { type: 'boolean' }),
        this.field(SETTING.relatedDiscussionsLimit, { type: 'number' }),
        this.field(SETTING.relatedMaxCandidates, { type: 'number' }),
        this.field(SETTING.graphMaxDepth, { type: 'number' }),
        this.field(SETTING.graphMaxPerHop, { type: 'number' }),
      ]),
      20
    );

    items.add(
      'maintenance',
      this.section('maintenance', [this.field(SETTING.cacheTtl, { type: 'number' }), this.field(SETTING.brokenRetentionDays, { type: 'number' })]),
      10
    );

    // Drawing our own fields replaces the renderer that would have shown
    // settings other extensions registered against this page, so they are
    // put back here rather than silently dropped.
    const registered = app.registry.getSettings(this.extension.id);

    if (registered) {
      registered.forEach((entry: any) => {
        if (typeof entry !== 'function' && entry.setting) {
          this.setting(entry.setting, '', entry.label);
        }
      });

      items.add(
        'extensions',
        this.section(
          'extensions',
          registered.map((setting) => this.buildSettingComponent(setting))
        ),
        0
      );
    }

    return items;
  }
}
