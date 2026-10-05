import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import FormGroup from 'flarum/common/components/FormGroup';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Stream from 'flarum/common/utils/Stream';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import { relationOptions } from '../utils/relations';
import type Reference from '../../common/models/Reference';

export interface ILinkFormModalAttrs extends IFormModalAttrs {
  /** Add a link from this discussion. */
  discussion?: Discussion;
  /** Or edit this link. */
  reference?: Reference;
  onsave?: () => void;
}

/**
 * Add a link by hand, or change the type and note of an existing one.
 *
 * `FormModal`, not `Modal`: only its wrapper is a `<form>`, and without one a
 * submit button has no form owner, so `onsubmit` never fires.
 */
export default class LinkFormModal<CustomAttrs extends ILinkFormModalAttrs = ILinkFormModalAttrs> extends FormModal<CustomAttrs> {
  private relation!: Stream<string>;
  private note!: Stream<string>;
  private query = Stream('');

  private target: Discussion | null = null;
  private results: Discussion[] = [];
  private searching = false;
  private timeout?: number;
  // Only the latest search may draw its results: a slow answer to "rus"
  // arriving after the one for "rust" would otherwise replace it.
  private searchId = 0;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.relation = Stream(this.attrs.reference?.relationType() || 'references');
    this.note = Stream(this.attrs.reference?.note() || '');
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    window.clearTimeout(this.timeout);
  }

  className(): string {
    return 'LinkFormModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans(`datlechin-references.forum.form.${this.attrs.reference ? 'edit_title' : 'add_title'}`);
  }

  content(): Mithril.Children {
    const adding = !this.attrs.reference;

    return (
      <div className="Modal-body">
        <Form>
          {adding && (
            <div className="Form-group">
              <label for="LinkFormModal-target">{app.translator.trans('datlechin-references.forum.form.target_label')}</label>
              {this.target ? this.selected(this.target) : this.picker()}
            </div>
          )}

          <FormGroup
            type="select"
            stream={this.relation}
            options={relationOptions()}
            label={app.translator.trans('datlechin-references.forum.form.type_label')}
          />

          <FormGroup
            type="textarea"
            stream={this.note}
            label={app.translator.trans('datlechin-references.forum.form.note_label')}
            placeholder={extractText(app.translator.trans('datlechin-references.forum.form.note_placeholder'))}
          />

          <div className="Form-group Form-controls">
            <Button type="submit" className="Button Button--primary" loading={this.loading} disabled={adding && !this.target}>
              {app.translator.trans(`datlechin-references.forum.form.${adding ? 'add_button' : 'save_button'}`)}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  private picker(): Mithril.Children {
    const typed = this.query().trim();

    return (
      <div className="LinkFormModal-picker">
        {/* Named after the field the server reports errors against, so a 422 focuses it. */}
        <input
          id="LinkFormModal-target"
          className="FormControl"
          name="targetId"
          autocomplete="off"
          placeholder={extractText(app.translator.trans('datlechin-references.forum.form.target_placeholder'))}
          value={this.query()}
          oninput={(event: InputEvent) => this.search((event.target as HTMLInputElement).value)}
        />
        {this.searching ? (
          <LoadingIndicator display="block" size="small" />
        ) : (
          typed !== '' && (
            <ul className="LinkFormModal-results">
              {this.results.length ? (
                this.results.map((discussion) => (
                  <li key={discussion.id()}>
                    <button type="button" className="LinkFormModal-result" onclick={() => this.pick(discussion)}>
                      {discussion.title()}
                    </button>
                  </li>
                ))
              ) : (
                <li className="LinkFormModal-empty">{app.translator.trans('datlechin-references.forum.form.no_results')}</li>
              )}
            </ul>
          )
        )}
      </div>
    );
  }

  private selected(discussion: Discussion): Mithril.Children {
    return (
      <div className="LinkFormModal-selected">
        <span className="LinkFormModal-selectedTitle">{discussion.title()}</span>
        <Button className="Button Button--link" onclick={() => this.pick(null)}>
          {app.translator.trans('datlechin-references.forum.form.change_button')}
        </Button>
      </div>
    );
  }

  private pick(discussion: Discussion | null): void {
    this.target = discussion;
    this.alertAttrs = null;
  }

  private search(value: string): void {
    this.query(value);
    this.target = null;

    window.clearTimeout(this.timeout);

    const typed = value.trim();

    if (typed === '') {
      this.results = [];
      this.searching = false;

      return;
    }

    this.searching = true;
    this.timeout = window.setTimeout(() => this.load(typed, ++this.searchId), 250);
  }

  private async load(typed: string, searchId: number): Promise<void> {
    const own = this.attrs.discussion?.id();
    let found: Discussion[] = [];

    try {
      // A number is looked up as an id first, and searched as text only when
      // no discussion has it.
      const byId = /^\d+$/.test(typed) ? await app.store.find<Discussion>('discussions', typed).catch(() => null) : null;

      found = byId ? [byId] : await app.store.find<Discussion[]>('discussions', { filter: { q: typed }, page: { limit: 6 } });
    } catch {
      found = [];
    }

    if (searchId !== this.searchId) return;

    // The server refuses a link from a discussion to itself, so it is not offered.
    this.results = found.filter((discussion) => discussion.id() !== own).slice(0, 5);
    this.searching = false;

    m.redraw();
  }

  onsubmit(event: SubmitEvent) {
    event.preventDefault();

    const attributes: Record<string, unknown> = { relationType: this.relation(), note: this.note().trim() || null };
    let request: Promise<unknown>;

    if (this.attrs.reference) {
      request = this.attrs.reference.save(attributes);
    } else if (this.target && this.attrs.discussion) {
      request = app.store.createRecord('post-references').save({
        ...attributes,
        targetType: 'discussions',
        targetId: Number(this.target.id()),
        relationships: { sourceDiscussion: this.attrs.discussion },
      });
    } else {
      return;
    }

    this.loading = true;

    request
      .then(() => {
        this.hide();
        this.attrs.onsave?.();
      })
      .catch((error) => {
        this.loading = false;
        // Shown in the modal, and on a 422 the field with the problem gets focus.
        this.onerror(error);
      });
  }
}
