import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import FormGroup from 'flarum/common/components/FormGroup';
import Stream from 'flarum/common/utils/Stream';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

export interface IManualReferenceModalAttrs extends IFormModalAttrs {
  discussion: Discussion;
}

/**
 * `FormModal`, not `Modal`: only its wrapper is a `<form>`, and without one a
 * submit button has no form owner, so `onsubmit` never fires.
 */
export default class ManualReferenceModal<
  CustomAttrs extends IManualReferenceModalAttrs = IManualReferenceModalAttrs,
> extends FormModal<CustomAttrs> {
  private targetId = Stream('');
  private relation = Stream('references');
  private note = Stream('');

  className(): string {
    return 'ManualReferenceModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-references.forum.manual.title');
  }

  content(): Mithril.Children {
    const options: Record<string, string> = {};

    // Read from the forum payload rather than listed here, so the picker
    // cannot offer a relation the column would refuse.
    for (const relation of app.forum.attribute<string[]>('datlechin-references.relationTypes') || []) {
      options[relation] = extractText(app.translator.trans(`datlechin-references.forum.relation.${relation}`));
    }

    return (
      <div className="Modal-body">
        <Form>
          <FormGroup
            type="text"
            inputmode="numeric"
            stream={this.targetId}
            label={app.translator.trans('datlechin-references.forum.manual.target_label')}
            placeholder={extractText(app.translator.trans('datlechin-references.forum.manual.target_placeholder'))}
          />

          <FormGroup
            type="select"
            stream={this.relation}
            options={options}
            label={app.translator.trans('datlechin-references.forum.manual.relation_label')}
          />

          <FormGroup
            type="textarea"
            stream={this.note}
            label={app.translator.trans('datlechin-references.forum.manual.note_label')}
            placeholder={extractText(app.translator.trans('datlechin-references.forum.manual.note_placeholder'))}
          />

          <div className="Form-group Form-controls">
            <Button type="submit" className="Button Button--primary" loading={this.loading} disabled={!this.targetId()}>
              {app.translator.trans('datlechin-references.forum.manual.submit_button')}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  onsubmit(event: SubmitEvent) {
    event.preventDefault();

    this.loading = true;

    app.store
      .createRecord('post-references')
      .save({
        targetType: 'discussions',
        targetId: Number(this.targetId()),
        relationType: this.relation(),
        note: this.note() || null,
        relationships: { sourceDiscussion: this.attrs.discussion },
      })
      .then(() => {
        app.modal.close();
        m.redraw();
      })
      .catch((error) => {
        this.loading = false;
        // Rendered into the modal's own alert slot, and on a 422 the offending
        // field takes focus. Discarding it failed silently.
        this.onerror(error);
      });
  }
}
