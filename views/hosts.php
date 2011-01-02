<?php   //hosts.php     //display page for host details 


// Nagios V-Shell
// Copyright (c) 2010 Nagios Enterprises, LLC.
// Written by Mike Guthrie <mguthrie@nagios.com>
//
// LICENSE:
//
// This work is made available to you under the terms of Version 2 of
// the GNU General Public License. A copy of that license should have
// been provided with this software, but in any event can be obtained
// from http://www.fsf.org.
// 
// This work is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
// General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
// 02110-1301 or visit their web page on the internet at
// http://www.fsf.org.
//
//
// CONTRIBUTION POLICY:
//
// (The following paragraph is not intended to limit the rights granted
// to you to modify and distribute this software under the terms of
// licenses that may apply to the software.)
//
// Contributions to this software are subject to your understanding and acceptance of
// the terms and conditions of the Nagios Contributor Agreement, which can be found 
// online at:
//
// http://www.nagios.com/legal/contributoragreement/
//
//
// DISCLAIMER:
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
// PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
// HOLDERS BE LIABLE FOR ANY CLAIM FOR DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
// OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
// GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, STRICT LIABILITY, TORT (INCLUDING 
// NEGLIGENCE OR OTHERWISE) OR OTHER ACTION, ARISING FROM, OUT OF OR IN CONNECTION 
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


//displays hosts table for any array of hosts 
function display_hosts($hosts, $start,$limit)
{

	$table = '<table class="statustable"><tr> 
			<th>Host Name</th>
			<th>Status</th>
			<th>Duration</th>
			<th>Attempt</th>
			<th>Last Check</th>
			<th>Status Information</th></tr>';

	$resultsCount = count($hosts);
	//if results are greater than number that the page can display, create page links
	//if no result limit is defined, page will display all results.  Default limit is 100 results
	//calculate number of pages 
	$pageCount = (($resultsCount / $limit) < 1) ? 1 : intval($resultsCount/$limit);
	
	//check if more than one page is needed 
	if($pageCount * $limit < $resultsCount)
	{
		$table .= do_pagenumbers($pageCount,$start,$limit,$resultsCount,'hosts');
	}
	
	//creates notes for total results as well as form for setting page limits 
	$table .= do_result_notes($start,$limit,$resultsCount,'hosts');	

	$hostnames = array_keys($hosts);
	sort($hostnames);

	//begin looping table results 
	for($i=$start; $i<=($start+$limit); $i++)
	{
		if ($i >= $resultsCount) break;
		if(!isset($hosts[$hostnames[$i]])) continue; //skip undefined indexes of hosts array 
		$host = $hosts[$hostnames[$i]];

		$tr = get_color_code($host); // CSS style class based on status 
		$url = htmlentities(BASEURL.'index.php?mode=filter&type=hostdetail&arg='.$host['host_name']);
		$comments = comment_icon($host['host_name']); //has comments icon 
		$dt_icon = downtime_icon($host['scheduled_downtime_depth']); //scheduled downtime icon 
		$tablerow = <<<TABLEROW
	
		<tr>	
			<td><a href="{$url}">{$host['host_name']}</a>{$comments}{$dt_icon}</td><td class="{$tr}">{$host['current_state']}</td>
			<td>{$host['duration']}</td>
			<td>{$host['attempt']}</td>
			<td>{$host['last_check']}</td>
			<td>{$host['plugin_output']}</td>
		</tr>
			
TABLEROW;
		#print $tablerow;
		$table .= $tablerow;
	}
	
	
	#print '</table>';
	$table .= '</table>';

	//print the page numbers here accordingly 
	return $table;
} 
?>
