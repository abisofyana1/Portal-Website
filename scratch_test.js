import { normalizeOfficeHtml, parseOfficeListStyles } from './resources/js/utils/officePasteNormalizer.js';

const testHtml = `<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40"><head><style>
@list l5:level1
	{mso-level-number-format:bullet;
	mso-level-text:\\F0D8;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	margin-left:18.0pt;
	text-indent:-18.0pt;
	font-family:Wingdings;}
@list l5:level2
	{mso-level-number-format:bullet;
	mso-level-text:\\F076;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Wingdings;}
</style></head><body>
<p class="MsoListParagraph" style="margin-left:18.0pt;text-indent:-18.0pt;mso-list:l5 level1 lfo1">
<!--[if !supportLists]-->
<span style="font-family:Wingdings"><span style="mso-list:Ignore">&#xF0D8;<span>&nbsp;&nbsp;&nbsp;&nbsp;</span></span></span>
<!--[endif]-->
Penawaran Program dengan Reward Tiering sesuai syarat transaksi :
</p>
<p class="MsoListParagraph" style="margin-left:36.0pt;text-indent:-18.0pt;mso-list:l5 level2 lfo1">
<!--[if !supportLists]-->
<span style="font-family:Wingdings"><span style="mso-list:Ignore">&#xF076;<span>&nbsp;&nbsp;&nbsp;&nbsp;</span></span></span>
<!--[endif]-->
Bonus 10.000 BNI Rewards Point
</p>
</body></html>`;

console.log('List Definitions:', parseOfficeListStyles(testHtml));
try {
    const res = normalizeOfficeHtml(testHtml);
    console.log('Normalized HTML:\n', res);
} catch (e) {
    console.error('Error normalizing:', e);
}
