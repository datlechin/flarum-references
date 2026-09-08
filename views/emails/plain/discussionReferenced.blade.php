<x-mail::plain.notification>
<x-slot:body>
{!! $translator->trans('datlechin-references.email.discussion_referenced.body', [
'{username}' => $blueprint->source->user?->display_name ?? '',
'{title}' => $blueprint->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->source->discussion_id, 'near' => $blueprint->source->number]),
]) !!}
</x-slot:body>
</x-mail::plain.notification>
