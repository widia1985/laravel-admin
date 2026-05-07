<div class="form-group">
    <label>{{ $label }}</label>
	<div class="input-group">
	<span class="input-group-addon">$</span>
    <input {!! $attributes !!} style="width: 120px; text-align: right;">
	</div>
    @include('admin::actions.form.help-block')
</div>