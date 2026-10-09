{{--
    The Data Privacy Notice as a self-styled document: the PDF
    (MasterController::dataPrivacy, through dompdf) and the dialogs on the old
    shell (layouts/master). Styles are inline because dompdf needs them so.

    The wording is not written here. It comes from privacy_notice() in
    app/Helpers/helpers.php, which the dialogs on layouts/app draw from too
    (layouts/app-privacy).
--}}
@php
    $notice = privacy_notice();
    // Links in the wording carry no styling of their own.
    $linked = fn ($html) => str_replace(' target="_blank">', ' target="_blank" style="color: #00695c; text-decoration: underline;">', $html);
@endphp
<div class="privacy-container" style="
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background-color: #ffffff;
    padding: 28px 32px;
    border-radius: 10px;
    color: #2d3748;
    font-size: 14.5px;
    line-height: 1.7;
">

  <div style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #00695c; padding-bottom: 15px;">
    <h2 style="font-size: 22px; font-weight: 700; color: #004d40; margin: 0 0 6px 0;">
      {{ $notice['title'] }}
    </h2>
    <p style="font-size: 13px; color: #4a5568; margin: 0; font-weight: 500;">
      {{ $notice['basis'] }}
    </p>
  </div>

  <p>
    {!! $linked($notice['intro']) !!}
  </p>

  @foreach($notice['sections'] as $section)
    <h4 style="color: #00695c; font-size: 16px; font-weight: 700; margin-top: 24px; margin-bottom: 10px; border-left: 4px solid #00695c; padding-left: 10px;">
      {{ $loop->iteration }}. {!! $section['heading'] !!}
    </h4>
    <p>{!! $linked($section['lead']) !!}</p>
    @if($section['items'])
      <ul style="margin-left: 20px; margin-top: 8px; margin-bottom: 16px;">
        @foreach($section['items'] as $item)
          <li>{!! $linked($item) !!}</li>
        @endforeach
      </ul>
    @endif
  @endforeach

  <div style="margin-top: 28px; border-top: 1px solid #e2e8f0; padding-top: 16px; text-align: center; font-size: 13px; color: #718096; line-height: 1.6;">
    <div><strong>{!! $notice['issuer'] !!}</strong></div>
    <div>System Developer &amp; Technical Service Provider: <strong>{{ $notice['developer'] }}</strong></div>
    <div style="margin-top: 4px;">&copy; {{ date('Y') }} All Rights Reserved.</div>
  </div>

</div>
