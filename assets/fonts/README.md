# Font metrics for the PDF export

`core/helvetica*.json` and `core/courier*.json` are the metric definitions of the PDF core fonts
Helvetica and Courier (regular, bold, oblique, bold oblique) in the format
`tecnickcom/tc-lib-pdf-font` reads. They are metrics only: the fonts themselves are built into
every PDF viewer and nothing is embedded. LGPL, like the library.

Provenance: the Adobe core-14 AFM files of `tecnickcom/tc-font-mirror` 2.4.0, converted with the
library's `Import` class (`new Import( $afm, $outdir, 'Core' )`). The export names each file
explicitly and lists this directory among the document's trusted paths, so no `K_PATH_FONTS`
constant is needed and none is defined (another plugin may define its own).
