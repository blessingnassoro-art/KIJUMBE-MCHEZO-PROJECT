const state = {

    members: [],

    mchezoGroups: []

};

const pages={
    dashboard:{title:"Dashboard",sub:"Overview of your Mchezo group"},
    mchezo:{title:"Mchezo",sub:"Manage group rules and current cycle"},
    members:{title:"Members",sub:"Manage Mchezo participants"},
    rounds:{title:"Rounds",sub:"Track contribution rounds and collection"},
    contributions:{title:"Contributions",sub:"Record and monitor member contributions"},
    rotation:{title:"Rotation",sub:"Manage contribution receiving order"},
    payments:{title:"Payments",sub:"View payment records and sandbox payment demo"},
    meetings:{title:"Meetings",sub:"Manage meetings and attendance"},
    fines:{title:"Fines",sub:"Record and monitor member fines"},
    transactions:{title:"Transactions",sub:"Track the Mchezo financial ledger"},
    reports:{title:"Reports",sub:"View summaries and financial reports"},
    auditLogs: {title: "Audit Logs",sub: "Track important actions performed in KIJUMBE."},
    settings:{title:"Settings",sub:"Configure KIJUMBE"}
};

const content=document.getElementById("content"), 
      title=document.getElementById("pageTitle"), 
      subtitle=document.getElementById("pageSubtitle");
const sidebar=document.getElementById("sidebar"), 
      main=document.querySelector(".main"), 
      overlay=document.getElementById("overlay");

function money(n){
    return "TSh "+Number(n).toLocaleString("en-US")
  }

function avatar(name)
    {return name.split(" ").map(x=>x[0]).slice(0,2).join("").toUpperCase()

    }

function badge(status){
    const cls=status==="Paid"||status==="Active"||status==="Completed"||status==="Successful"?"success":
             status==="Pending"||status==="Upcoming"?"warning":status==="Failed"||status==="Overdue"?"danger":"neutral";
  return `<span class="badge ${cls}">${status}</span>`;
}
function card(titleText,body,extra=""){
 return `<div class="card"><div class="section-head"><div><h3>${titleText}</h3></div>${extra}</div>${body}</div>`;
}


function escapeHtml(value) {

    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}



async function dashboard() {

    // Show loading state first
    content.innerHTML = `
        <div class="card">
            <p>Loading dashboard...</p>
        </div>
    `;

    try {

        const response = await fetch("Dashboard/get_dashboard.php");

        if (!response.ok) {
            throw new Error("Failed to load dashboard");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Failed to load dashboard");
        }

        const round = data.current_round;
        const currentTurn = data.current_turn;
        const upcomingTurn = data.upcoming_turn;

        /*
         * Current round information
         */
        const roundNumber = round
            ? round.round_number
            : "—";

        const contributionAmount = round
            ? Number(round.contribution_amount)
            : 0;

        const groupName = round
            ? round.group_name
            : "No active Mchezo group";

        /*
         * Collection information
         */
        const collected = round
            ? Number(round.collected_amount)
            : 0;

        const expected = round
            ? Number(round.expected_amount)
            : 0;

        const percentage = expected > 0
            ? Math.min((collected / expected) * 100, 100)
            : 0;

        /*
         * Current recipient
         */
        const currentRecipient = currentTurn
            ? currentTurn.full_name
            : "No current recipient";

        /*
         * Upcoming recipient
         */
        const upcomingRecipient = upcomingTurn
            ? upcomingTurn.full_name
            : "No upcoming turn";

        const upcomingRound = upcomingTurn
            ? upcomingTurn.round_number
            : "—";

        /*
         * Render dashboard
         */
        content.innerHTML = `

        <div class="hero">

            <h3>
                Good morning, ${currentUser.name} 👋
            </h3>

            <p>
                Here is the current status of
                <strong>${groupName}</strong>.
                Keep track of contributions, turns and payments from one place.
            </p>

            <div class="kpi-row">

                <div>
                    <strong>Round ${roundNumber}</strong>
                    <span>Current round</span>
                </div>

                <div>
                    <strong>${money(contributionAmount)}</strong>
                    <span>Member contribution</span>
                </div>

                <div>
                    <strong>${currentRecipient}</strong>
                    <span>Current recipient</span>
                </div>

            </div>

        </div>


        <div class="grid stats">

            ${stat(
                "Total Members",
                data.members.total,
                "Active members",
                "♙"
            )}

            ${stat(
                "Mchezo Groups",
                data.groups.total,
                "Active groups",
                "◎"
            )}

            ${stat(
                "Total Collected",
                money(data.total_collected),
                "Successful payments",
                "▣"
            )}

            ${stat(
                "Pending",
                data.pending_contributions,
                "Contributions awaiting payment",
                "!"
            )}

        </div>


        <div style="height:18px"></div>


        <div class="grid two">

            ${card(
                `Round ${roundNumber} Collection`,
                `

                <div style="
                    display:flex;
                    justify-content:space-between;
                    font-size:12px;
                    margin-bottom:8px
                ">

                    <span>Collected</span>

                    <strong>
                        ${money(collected)}
                        /
                        ${money(expected)}
                    </strong>

                </div>


                <div class="progress">

                    <span style="
                        width:${percentage}%
                    "></span>

                </div>


                <div style="
                    margin-top:13px;
                    font-size:11px;
                    color:var(--muted)
                ">

                    ${percentage.toFixed(0)}% of expected contributions collected.

                </div>


                <div class="alert" style="margin-top:15px">

                    Current recipient:
                    <strong>${currentRecipient}</strong>.

                </div>

                `,
                `<button
                    class="btn btn-light"
                    onclick="showPage('contributions')"
                >
                    View
                </button>`
            )}


            ${card(
                "Upcoming Turn",
                `

                <div style="
                    display:flex;
                    align-items:center;
                    gap:12px;
                    margin-bottom:14px
                ">

                    <div class="avatar">
                        ${upcomingRecipient !== "No upcoming turn"
                            ? avatar(upcomingRecipient)
                            : "—"}
                    </div>

                    <div>

                        <strong style="font-size:14px">
                            ${upcomingRecipient}
                        </strong>

                        <small
                            class="muted"
                            style="display:block;margin-top:3px"
                        >
                            Round ${upcomingRound} recipient
                        </small>

                    </div>

                </div>


                <div style="
                    font-size:11px;
                    color:var(--muted)
                ">
                    Expected payout
                </div>


                <div
                    class="amount"
                    style="font-size:20px;margin-top:3px"
                >
                    ${money(expected)}
                </div>


                <div style="margin-top:15px">

                    <span class="badge warning">
                        Upcoming
                    </span>

                </div>

                `
            )}

        </div>


        <div style="height:18px"></div>


        <div class="grid two">

            ${card(
                "Recent Contributions",
                contributionRows(data.recent_contributions)
            )}


            ${card(
                "Recent Payments",
                renderRecentPayments(data.recent_payments)
            )}

        </div>


        <div style="height:18px"></div>


        ${card(
            "Quick Actions",
            `
            <div class="quick-actions">

                <button
                    class="quick"
                    onclick="openModal('Add Member')"
                >
                    <strong>＋ Add Member</strong>
                    <span>Register a new participant</span>
                </button>


                <button
                    class="quick"
                    onclick="openModal('Record Contribution')"
                >
                    <strong>▣ Record Contribution</strong>
                    <span>Record a member payment</span>
                </button>


                <button
                    class="quick"
                    onclick="showPage('meetings')"
                >
                    <strong>□ New Meeting</strong>
                    <span>Create a group meeting</span>
                </button>


                <button
                    class="quick"
                    onclick="showPage('reports')"
                >
                    <strong>▤ View Reports</strong>
                    <span>Open financial summaries</span>
                </button>

            </div>
            `
        )}

        `;

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">

                <h3>Dashboard Error</h3>

                <p style="color:red">
                    Failed to load dashboard data.
                </p>

            </div>
        `;
    }
}

function renderRecentPayments(payments) {

    if (!payments || payments.length === 0) {

        return `
            <div class="empty-state">
                <p>No payments recorded yet.</p>
            </div>
        `;

    }

    return `
        <div class="timeline">

            ${payments.map(payment => `

                <div class="timeline-item">

                    <span class="dot"></span>

                    <div>

                        <strong>
                            Payment recorded
                        </strong>

                        <small>
                            ${payment.full_name}
                            paid
                            ${money(payment.amount)}
                            ·
                            ${payment.payment_method}
                        </small>

                    </div>

                </div>

            `).join("")}

        </div>
    `;
}


function stat(a,b,c,icon){return `<div class="card stat"><div><div class="stat-label">${a}</div><div class="stat-value">${b}</div><div class="stat-note">${c}</div></div><div class="stat-icon">${icon}</div></div>`}

function contributionRows(contributions = []) {

    if (!contributions || contributions.length === 0) {
        return `
            <div class="empty-state">
                <p>No recent contributions.</p>
            </div>
        `;
    }

    return `
        <div>
            ${contributions.map(c => `
                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    padding:10px 0;
                    border-bottom:1px solid var(--border);
                ">

                    <div>
                        <strong>
                            ${escapeHtml(c.full_name || "-")}
                        </strong>

                        <small
                            class="muted"
                            style="display:block;margin-top:3px"
                        >
                            Round ${escapeHtml(String(c.round_number || "-"))}
                        </small>
                    </div>

                    <div style="text-align:right">

                        <strong>
                            ${money(c.paid_amount || 0)}
                        </strong>

                        <small
                            class="muted"
                            style="display:block;margin-top:3px"
                        >
                            ${escapeHtml(c.status || "-")}
                        </small>

                    </div>

                </div>
            `).join("")}
        </div>
    `;
}

//add members

async function loadMembers() {

    try {

        const response = await fetch("Members/get_members.php");

        if (!response.ok) {
            throw new Error("Failed to load members");
        }

        const data = await response.json();

state.members = data.map(member => ({
    id: member.id,
    groupId: Number(member.group_id || 0),
    name: member.full_name,
    phone: member.phone,
    email: member.email,
    joinDate: member.join_date,
    role: "Member",
    status: member.status === "active" ? "Active" : "Inactive",
    group: member.group_name,
    currentMembers: Number(member.current_members || 0),
    maxMembers: Number(member.max_members || 0)
}));

//render members
 renderMembers();

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load members from database.
                </p>
            </div>
        `;
    }
}


async function loadFineMembers() {

    const select = document.getElementById("fineMemberId");

    if (!select) return;

    try {

        const response = await fetch("Members/get_members.php");

        const data = await response.json();

        if (!response.ok) {
            throw new Error("Failed to load members.");
        }

        const members = Array.isArray(data)
            ? data
            : data.members || [];

        select.innerHTML = `
            <option value="">
                Select member
            </option>

            ${members
                .filter(member => member.status === "active")
                .map(member => `
                    <option value="${member.id}">
                        ${escapeHtml(member.full_name)}
                    </option>
                `)
                .join("")
            }
        `;

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Failed to load members
            </option>
        `;
    }
}

async function loadFineMeetings() {

    const select = document.getElementById("fineMeetingId");

    if (!select) return;

    try {

        const response = await fetch("Meetings/get_meetings.php");

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to load meetings."
            );
        }

        select.innerHTML = `
            <option value="">
                No specific meeting
            </option>

            ${data.meetings.map(meeting => `
                <option value="${meeting.id}">
                    ${escapeHtml(meeting.title)}
                    - ${escapeHtml(meeting.meeting_date)}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Failed to load meetings
            </option>
        `;
    }
}

async function saveFine() {

    const memberId =
        document.getElementById("fineMemberId").value;

    const meetingId =
        document.getElementById("fineMeetingId").value;

    const amount =
        document.getElementById("fineAmount").value;

    const reason =
        document.getElementById("fineReason").value.trim();


    if (!memberId) {

        toast("Please select a member.");

        return;
    }


    if (!amount || Number(amount) <= 0) {

        toast("Please enter a valid fine amount.");

        return;
    }


    if (!reason) {

        toast("Please enter the reason for the fine.");

        return;
    }


    const formData = new FormData();

    formData.append("member_id", memberId);
    formData.append("meeting_id", meetingId);
    formData.append("amount", amount);
    formData.append("reason", reason);


    try {

        const response = await fetch(
            "Fines/add_fine.php",
            {
                method: "POST",
                body: formData
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Failed to record fine."
            );
        }


        toast("Fine recorded successfully.");

        closeModal();

        fines();


    } catch (error) {

        console.error(error);

        toast(error.message);

    }
}


function members() {

    content.innerHTML = `
        <div class="card">
            <p>Loading members...</p>
        </div>
    `;

    loadMembers();
}


 function renderMembers() {

        content.innerHTML = `
        <div class="card">

        <div class="section-head">

        <div>
        <h3>Group Members</h3>
        <p>
            ${state.members.length}
            members currently displayed
        </p>
        </div>

        <div style="display:flex;gap:8px">

        <input
            id="memberSearch"
            class="search"
            placeholder="Search members..."
        >

        <button
            class="btn btn-primary"
            onclick="openModal('Add Member')"
        >
            ＋ Add Member
        </button>

        </div>

        </div>


        <div class="table-wrap">

        <table class="table">

        <thead>

            <tr>
                <th>Member</th>
                <th>Phone</th>
                <th>Mchezo Group</th>
                <th>Capacity</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

        </thead>


        <tbody id="memberRows">

            ${state.members.map((m, i) => `

                <tr>

                    <td>
                        <div class="member-cell">

                            <div class="avatar">
                                ${avatar(m.name)}
                            </div>

                            <strong>
                                ${m.name}
                            </strong>

                        </div>
                    </td>


                    <td>
                        ${m.phone}
                    </td>


                    <td>
                        <strong>
                            ${m.group || "No Group"}
                        </strong>
                    </td>


                    <td>

                        <strong>
                            ${m.currentMembers} / ${m.maxMembers}
                        </strong>

                        <div
                            style="
                                font-size:12px;
                                margin-top:3px;
                            "
                        >

                            ${
                                m.currentMembers >= m.maxMembers

                                ? `
                                    <span style="color:#c62828;">
                                        Full
                                    </span>
                                `

                                : `
                                    <span style="opacity:.7;">
                                        ${m.maxMembers - m.currentMembers}
                                        space${m.maxMembers - m.currentMembers === 1 ? "" : "s"}
                                        left
                                    </span>
                                `
                            }

                        </div>

                    </td>


                    <td>
                        ${m.role}
                    </td>


                    <td>
                        ${badge(m.status)}
                    </td>


                    <td>

                        <button
                            class="btn btn-light"
                            onclick="editMember(${i})"
                        >
                            Edit
                        </button>

                        <button
                            class="btn btn-light"
                            onclick="deactivateMember(${m.id})"
                        >
                            Deactivate
                        </button>

                    </td>

                </tr>

            `).join("")}

        </tbody>

        </table>

        </div>

        </div>
        `;

        }

function members() {

    content.innerHTML = `
        <div class="card">
            <p>Loading members...</p>
        </div>
    `;

    loadMembers();
}
//deactivateMember

async function deactivateMember(id) {

    const confirmDeactivate = confirm(
        "Are you sure you want to deactivate this member?"
    );

    if (!confirmDeactivate) {
        return;
    }

    const formData = new FormData();

    formData.append("id", id);

    try {

        const response = await fetch(
            "Members/deactivate_member.php",
            {
                method: "POST",
                body: formData
            }
        );

        const result = await response.json();

        if (!result.success) {
            toast(result.message);
            return;
        }

        toast(result.message);

        showPage("members");

    } catch (error) {

        console.error(error);

        toast("Failed to deactivate member.");
    }
}

async function loadGroups() {

    try {

        const response = await fetch("Members/get_groups.php");

        if (!response.ok) {
            throw new Error("Failed to load groups");
        }

        const groups = await response.json();

        const groupSelect = document.getElementById("mGroupId");

        groupSelect.innerHTML = `
            <option value="">Select Mchezo Group</option>

            ${groups.map(group => `
                <option value="${group.id}">
                    ${group.group_name}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        const groupSelect = document.getElementById("mGroupId");

        if (groupSelect) {
            groupSelect.innerHTML = `
                <option value="">
                    Failed to load groups
                </option>
            `;
        }
    }
}


function simplePage(name,desc,body,button){
 content.innerHTML=`<div class="card"><div class="section-head"><div><h3>${name}</h3><p>${desc}</p></div>${button||""}</div>${body}</div>`;
}

function mchezo() {

    content.innerHTML = `
        <div class="card">
            <p>Loading Mchezo groups...</p>
        </div>
    `;

    loadMchezoGroups();
}

async function loadMchezoGroups() {

    try {

        const response = await fetch("Mchezo/get_groups.php");

        if (!response.ok) {
            throw new Error("Failed to load Mchezo groups");
        }

        const groups = await response.json();

        state.mchezoGroups = groups;

        renderMchezoGroups();

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load Mchezo groups from database.
                </p>
            </div>
        `;
    }
}



async function loadRoundGroups() {

    try {

        const response = await fetch("Mchezo/get_groups.php");

        if (!response.ok) {
            throw new Error("Failed to load groups");
        }

        const groups = await response.json();

        const groupSelect =
            document.getElementById("roundGroupId");

        groupSelect.innerHTML = `
            <option value="">
                Select Mchezo Group
            </option>

            ${groups
                .filter(group => group.status === "active")
                .map(group => `
                    <option value="${group.id}">
                        ${group.group_name}
                    </option>
                `)
                .join("")
            }
        `;

    } catch (error) {

        console.error(error);

        const groupSelect =
            document.getElementById("roundGroupId");

        if (groupSelect) {

            groupSelect.innerHTML = `
                <option value="">
                    Failed to load groups
                </option>
            `;
        }
    }
}
async function loadMeetingGroups() {

    try {

        const response = await fetch("Mchezo/get_groups.php");

        if (!response.ok) {
            throw new Error("Failed to load Mchezo groups");
        }

        const groups = await response.json();

        const select = document.getElementById("meetingGroupId");

        if (!select) {
            return;
        }


        if (!groups || groups.length === 0) {

            select.innerHTML = `
                <option value="">
                    No Mchezo groups available
                </option>
            `;

            return;
        }


        select.innerHTML = `
            <option value="">
                Select Mchezo Group
            </option>

            ${groups.map(group => `
                <option value="${group.id}">
                    ${escapeHtml(group.group_name)}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        const select = document.getElementById("meetingGroupId");

        if (select) {

            select.innerHTML = `
                <option value="">
                    Failed to load groups
                </option>
            `;

        }

    }
}

async function saveMeeting() {

    const groupId =
        document.getElementById("meetingGroupId").value;

    const title =
        document.getElementById("meetingTitle").value.trim();

    const meetingDate =
        document.getElementById("meetingDate").value;

    const location =
        document.getElementById("meetingLocation").value.trim();

    const agenda =
        document.getElementById("meetingAgenda").value.trim();


    /*
     * Validate required fields
     */

    if (!groupId) {

        toast("Please select a Mchezo group.");

        return;
    }


    if (!title) {

        toast("Please enter the meeting title.");

        return;
    }


    if (!meetingDate) {

        toast("Please select the meeting date.");

        return;
    }


    /*
     * Create form data
     */

    const formData = new FormData();

    formData.append("group_id", groupId);
    formData.append("title", title);
    formData.append("meeting_date", meetingDate);
    formData.append("location", location);
    formData.append("agenda", agenda);


    try {

        const response = await fetch(
            "Meetings/add_meeting.php",
            {
                method: "POST",
                body: formData
            }
        );


        const data = await response.json();


        if (!data.success) {

            toast(
                data.message ||
                "Failed to create meeting."
            );

            return;
        }


        /*
         * Success
         */

        toast("Meeting created successfully.");

        closeModal();


        /*
         * Reload meetings from MySQL
         */

        meetings();


    } catch (error) {

        console.error(error);

        toast("Failed to create meeting.");

    }
}


async function saveRound() {

    const groupId =
        document.getElementById("roundGroupId").value;

    const roundNumber =
        document.getElementById("roundNumber").value;

    const dueDate =
        document.getElementById("roundDueDate").value;


    if (!groupId || !roundNumber || !dueDate) {

        toast("Please fill in all required fields.");

        return;
    }


    const formData = new FormData();

    formData.append("group_id", groupId);
    formData.append("round_number", roundNumber);
    formData.append("due_date", dueDate);


    try {

        const response = await fetch(
            "Rounds/add_round.php",
            {
                method: "POST",
                body: formData
            }
        );


        const result = await response.json();


        if (!result.success) {

            toast(result.message);

            return;
        }


        closeModal();

        toast(result.message);

        showPage("rounds");


    } catch (error) {

        console.error(error);

        toast("Failed to create round.");

    }
}


function renderMchezoGroups() {

content.innerHTML = `
 <div class="card">

    <div class="section-head">

        <div>
            <h3>Mchezo Groups</h3>

            <p>
                ${state.mchezoGroups.length}
                groups currently displayed
            </p>
        </div>

        <button
            class="btn btn-primary"
            onclick="openModal('Add Mchezo Group')"
        >
            ＋ Add Group
        </button>

    </div>


    <div class="table-wrap">

  <table class="table">

      <thead>

          <tr>
              <th>Group</th>
              <th>Contribution</th>
              <th>Frequency</th>
              <th>Cycle</th>
              <th>Start Date</th>
              <th>Status</th>
              <th>Action</th>
          </tr>

      </thead>


      <tbody>

          ${state.mchezoGroups.map((group, i) => `

              <tr>

                  <td>
                      <strong>
                          ${group.group_name}
                      </strong>
                  </td>

                  <td>
                      ${money(group.contribution_amount)}
                  </td>

                  <td>
                      ${group.frequency}
                  </td>

                  <td>
                      ${group.cycle_length} rounds
                  </td>

                  <td>
                      ${group.start_date}
                  </td>

                  <td>
                      ${badge(
                          group.status === "active"
                              ? "Active"
                              : group.status === "inactive"
                                  ? "Inactive"
                                  : "Completed"
                      )}
                  </td>

                  <td>

                <button
                    class="btn btn-light"
                    onclick="viewMchezoMembers(${group.id})"
                >
                    View Members
                </button>

                <button
                    class="btn btn-light"
                    onclick="editMchezoGroup(${i})"
                >
                    Edit
                </button>

            </td>

              </tr>

          `).join("")}

          

      </tbody>

  </table>

    </div>

</div>
`;
}

async function viewMchezoMembers(groupId) {

    content.innerHTML = `
        <div class="card">
            <p>Loading group members...</p>
        </div>
    `;

    try {

        const response = await fetch(
            `Members/get_group_members.php?group_id=${groupId}`
        );

        if (!response.ok) {
            throw new Error("Failed to load group members");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.message || "Failed to load group members"
            );
        }

        renderMchezoMembers(data.members);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">

                <p style="color:red;">
                    Failed to load members for this Mchezo group.
                </p>

                <button
                    class="btn btn-light"
                    onclick="loadMchezoGroups()"
                >
                    ← Back to Groups
                </button>

            </div>
        `;
    }
}



function renderMchezoMembers(members) {

    const groupName =
        members.length > 0
            ? members[0].group_name
            : "Mchezo Group";


    content.innerHTML = `

        <div class="card">

            <div class="section-head">

                <div>

                    <h3>${groupName}</h3>

                    <p>
                        ${members.length}
                        member${members.length === 1 ? "" : "s"}
                    </p>

                </div>

                <button
                    class="btn btn-light"
                    onclick="loadMchezoGroups()"
                >
                    ← Back to Groups
                </button>

            </div>


            ${
                members.length === 0

                ? `
                    <div style="padding:20px;">
                        <p>
                            No members belong to this Mchezo group yet.
                        </p>
                    </div>
                `

                : `

                <div class="table-wrap">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Join Date</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            ${members.map(member => `

                                <tr>

                                    <td>
                                        <strong>
                                            ${member.full_name}
                                        </strong>
                                    </td>

                                    <td>
                                        ${member.phone}
                                    </td>

                                    <td>
                                        ${member.email || "-"}
                                    </td>

                                    <td>
                                        ${member.join_date}
                                    </td>

                                    <td>
                                        ${badge(
                                            member.status === "active"
                                                ? "Active"
                                                : "Inactive"
                                        )}
                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>

                `
            }

        </div>
    `;
}
function editMchezoGroup(index) {

    const group = state.mchezoGroups[index];

    openEditMchezoGroupModal(group);
}





function openEditMchezoGroupModal(group) {

    const body = document.getElementById("modalBody");

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Group Name</label>

                <input
                    id="editGroupName"
                    value="${group.group_name}"
                >

            </div>


            <div class="field">

                <label>Contribution Amount</label>

                <input
                    id="editGroupAmount"
                    type="number"
                    min="1"
                    value="${group.contribution_amount}"
                >

            </div>


            <div class="field">

                <label>Frequency</label>

                <select id="editGroupFrequency">

                    <option
                        value="weekly"
                        ${group.frequency === "weekly"
                            ? "selected"
                            : ""}
                    >
                        Weekly
                    </option>

                    <option
                        value="monthly"
                        ${group.frequency === "monthly"
                            ? "selected"
                            : ""}
                    >
                        Monthly
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Cycle Length</label>

                <input
                    id="editGroupCycle"
                    type="number"
                    min="1"
                    value="${group.cycle_length}"
                >

            </div>


            <div class="field">

                <label>Start Date</label>

                <input
                    id="editGroupStartDate"
                    type="date"
                    value="${group.start_date}"
                >

            </div>


            <div class="field">

                <label>Status</label>

                <select id="editGroupStatus">

                    <option
                        value="active"
                        ${group.status === "active"
                            ? "selected"
                            : ""}
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        ${group.status === "inactive"
                            ? "selected"
                            : ""}
                    >
                        Inactive
                    </option>

                    <option
                        value="completed"
                        ${group.status === "completed"
                            ? "selected"
                            : ""}
                    >
                        Completed
                    </option>

                </select>

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="updateMchezoGroup(${group.id})"
            >
                Update Group
            </button>

        </div>

                <div class="field">
                <label>Maximum Members</label>
                <input
                id="editGroupMaxMembers"
                type="number"
                min="1"
                value="${group.max_members || 10}">
        </div>

    `;

    document.getElementById("modalTitle").textContent =
        "Edit Mchezo Group";

    document.getElementById("modal").classList.remove("hidden");
}








async function updateMchezoGroup(id) {

    const groupName =
        document.getElementById("editGroupName").value.trim();

    const contributionAmount =
        document.getElementById("editGroupAmount").value;

    const frequency =
        document.getElementById("editGroupFrequency").value;

    const cycleLength =
        document.getElementById("editGroupCycle").value;

    const maxMembers =
        document.getElementById("editGroupMaxMembers").value;
    const startDate =
        document.getElementById("editGroupStartDate").value;

    const status =
        document.getElementById("editGroupStatus").value;


    // Validate form
    if (
        !groupName ||
        !contributionAmount ||
        !frequency ||
        !cycleLength ||
        !maxMembers ||
        !startDate ||
        !status
    ) {
        toast("Please fill in all required fields.");
        return;
    }
if (Number(maxMembers) < 1) {
    toast("Maximum members must be at least 1.");
    return;
}

    // Create FormData AFTER collecting all values
    const formData = new FormData();

    formData.append("id", id);
    formData.append("group_name", groupName);
    formData.append("contribution_amount", contributionAmount);
    formData.append("frequency", frequency);
    formData.append("cycle_length", cycleLength);
    formData.append("max_members",maxMembers);
    formData.append("start_date", startDate);
    formData.append("status", status);


    try {

        const response = await fetch(
            "Mchezo/update_group.php",
            {
                method: "POST",
                body: formData
            }
        );


        // Read PHP response as text first
        const text = await response.text();

        console.log("PHP RESPONSE:", text);


        // Convert response to JSON
        let result;

        try {
            result = JSON.parse(text);
        } catch (error) {

            console.error(
                "Invalid JSON returned by PHP:",
                text
            );

            toast(
                "PHP returned an invalid response. Check Console."
            );

            return;
        }


        // Check PHP result
        if (!result.success) {

            toast(result.message);

            return;
        }


        // Success
        closeModal();

        toast(result.message);

        showPage("mchezo");


    } catch (error) {

        console.error(
            "Update Mchezo Group Error:",
            error
        );

        toast(
            "Failed to update Mchezo group."
        );
    }
}






function rounds() {

    content.innerHTML = `
        <div class="card">
            <p>Loading rounds...</p>
        </div>
    `;

    loadRounds();
}

async function loadRounds() {

    try {

        const response = await fetch("Rounds/get_rounds.php");

        if (!response.ok) {
            throw new Error("Failed to load rounds");
        }

        const rounds = await response.json();

        renderRounds(rounds);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load rounds from database.
                </p>
            </div>
        `;
    }
}


function renderRounds(rounds) {

window.roundsData = rounds;

    content.innerHTML = `
<div class="card">

    <div class="section-head">

        <div>
            <h3>Contribution Rounds</h3>

            <p>
                ${rounds.length}
                rounds currently displayed
            </p>
        </div>

        <button
            class="btn btn-primary"
            onclick="openModal('Create Round')"
        >
            ＋ New Round
        </button>

    </div>

<div class="table-wrap">

<table class="table">

<thead>
<tr>
    <th>Group</th>
    <th>Round</th>
    <th>Due Date</th>
    <th>Expected</th>
    <th>Collected</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

${
    rounds.length === 0

    ?

    `
    <tr>
        <td colspan="6" style="text-align:center;">
            No rounds found.
        </td>
    </tr>
    `

    :

    rounds.map(round => `

        <tr>

            <td>
                <strong>
                    ${round.group_name}
                </strong>
            </td>

            <td>
                Round ${round.round_number}
            </td>

            <td>
                ${round.due_date}
            </td>

            <td class="amount">
                ${money(round.expected_amount)}
            </td>

            <td class="amount">
                ${money(round.collected_amount)}
            </td>

            <td>
                ${badge(
                    round.status === "upcoming"
                        ? "Upcoming"
                        : round.status === "active"
                            ? "Active"
                            : "Completed"
                )}
            </td>

            <td>
                <button
                        class="btn btn-light"
                        onclick="editRound(${round.id})"
                    >
                          Edit
                </button>
    </td>

        </tr>

            `).join("")
        }

    </tbody>

</table>

</div>

</div>
`;
}


function openEditRoundModal(round) {

    const body =
        document.getElementById("modalBody");

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Mchezo Group</label>

                <input
                    value="${round.group_name}"
                    disabled
                >

            </div>


            <div class="field">

                <label>Round Number</label>

                <input
                    id="editRoundNumber"
                    type="number"
                    min="1"
                    value="${round.round_number}"
                >

            </div>


            <div class="field">

                <label>Due Date</label>

                <input
                    id="editRoundDueDate"
                    type="date"
                    value="${round.due_date}"
                >

            </div>


            <div class="field">

                <label>Status</label>

                <select id="editRoundStatus">

                    <option
                        value="upcoming"
                        ${round.status === "upcoming"
                            ? "selected"
                            : ""}
                    >
                        Upcoming
                    </option>

                    <option
                        value="active"
                        ${round.status === "active"
                            ? "selected"
                            : ""}
                    >
                        Active
                    </option>

                    <option
                        value="completed"
                        ${round.status === "completed"
                            ? "selected"
                            : ""}
                    >
                        Completed
                    </option>

                </select>

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>

            <button
                class="btn btn-primary"
                onclick="updateRound(${round.id})"
            >
                Update Round
            </button>

        </div>
    `;

    document.getElementById("modalTitle").textContent =
        "Edit Round";

    document.getElementById("modal")
        .classList.remove("hidden");
}

async function updateRound(id) {

    const roundNumber =
        document.getElementById("editRoundNumber").value;

    const dueDate =
        document.getElementById("editRoundDueDate").value;

    const status =
        document.getElementById("editRoundStatus").value;


    if (!roundNumber || !dueDate || !status) {

        toast("Please fill in all required fields.");

        return;
    }


    const formData = new FormData();

    formData.append("id", id);
    formData.append("round_number", roundNumber);
    formData.append("due_date", dueDate);
    formData.append("status", status);


    try {

        const response = await fetch(
            "Rounds/update_round.php",
            {
                method: "POST",
                body: formData
            }
        );


        const result = await response.json();


        if (!result.success) {

            toast(result.message);

            return;
        }


        closeModal();

        toast(result.message);

        showPage("rounds");


    } catch (error) {

        console.error(error);

        toast("Failed to update round.");
    }
}

function editRound(id) {

    const round = window.roundsData.find(
        r => Number(r.id) === Number(id)
    );

    if (!round) {
        toast("Round not found.");
        return;
    }

    openEditRoundModal(round);
}

function contributions() {

    content.innerHTML = `
        <div class="card">

            <div class="card-header">

                <div>
                    <h3>Contributions</h3>
                    <p>Track member contributions and payments.</p>
                </div>

                <button
                    class="btn"
                    onclick="openGenerateContributionModal()">
                    Generate Contributions
                </button>

            </div>

            <div id="contributionsContent">
                <p>Loading contributions...</p>
            </div>

        </div>
    `;

    loadContributions();
}

async function openGenerateContributionModal() {

    try {

        const response = await fetch(
            "Rounds/get_rounds.php"
        );

        const rounds = await response.json();

        if (!response.ok || rounds.success === false) {
            throw new Error(
                rounds.message || "Failed to load rounds."
            );
        }

        if (!rounds || rounds.length === 0) {
            alert("No rounds are available.");
            return;
        }

        modalTitle.textContent = "Generate Contributions";

        modalBody.innerHTML = `
            <form id="generateContributionForm">

                <div class="field">
                    <label for="contributionRound">
                        Select Round
                    </label>

                    <select id="contributionRound" required>

                        <option value="">
                            Select round
                        </option>

                        ${rounds.map(round => `
                            <option value="${round.id}">
                                ${round.group_name}
                                - Round ${round.round_number}
                                - Due ${round.due_date}
                            </option>
                        `).join("")}

                    </select>
                </div>

                <div class="actions">

                    <button
                        type="button"
                        class="btn btn-light"
                        onclick="closeModal()">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Generate Contributions
                    </button>

                </div>

            </form>
        `;

        // IMPORTANT:
        // Your modal uses the "hidden" class
        document
            .getElementById("modal")
            .classList.remove("hidden");

        document
            .getElementById("generateContributionForm")
            .addEventListener(
                "submit",
                generateContributions
            );

    } catch (error) {

        alert(error.message);
        console.error(error);

    }
}



async function generateContributions(event) {

    event.preventDefault();

    const roundId =
        document.getElementById(
            "contributionRound"
        ).value;

    if (!roundId) {

        alert("Please select a round.");

        return;
    }

    try {

        const formData = new FormData();

        formData.append(
            "round_id",
            roundId
        );

        const response = await fetch(
            "Contributions/generate_contributions.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to generate contributions."
            );
        }

        alert(data.message);

        document.getElementById("modal").classList.add("hidden");

        loadContributions();

    } catch (error) {

        alert(error.message);

        console.error(error);
    }
}

async function loadContributions() {

    try {

        const response = await fetch(
            "Contributions/get_contributions.php"
        );

        if (!response.ok) {
            throw new Error("Failed to load contributions");
        }

        const contributions = await response.json();

        renderContributions(contributions);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load contributions from database.
                </p>
            </div>
        `;
    }
}

function renderContributions(contributions) {

    window.contributionsData = contributions;

    const container =
        document.getElementById("contributionsContent");

    if (!container) {
        console.error(
            "contributionsContent container not found."
        );
        return;
    }

    if (!contributions.length) {

        container.innerHTML = `
            <div class="alert">
                No contribution records found.
                Generate contributions for a round first.
            </div>
        `;

        return;
    }

    const totalExpected = contributions.reduce(
        (total, contribution) =>
            total + Number(contribution.expected_amount),
        0
    );

    const totalPaid = contributions.reduce(
        (total, contribution) =>
            total + Number(contribution.paid_amount),
        0
    );

    const remaining =
        totalExpected - totalPaid;


    container.innerHTML = `

        <!-- Contribution Statistics -->

        <div class="grid three">

            ${stat(
                "Total Expected",
                money(totalExpected),
                "Expected contributions",
                "₿"
            )}

            ${stat(
                "Total Collected",
                money(totalPaid),
                "Amount received",
                "▣"
            )}

            ${stat(
                "Remaining",
                money(remaining),
                "Outstanding contributions",
                "!"
            )}

        </div>


        <div style="height:18px"></div>


        <!-- Member Contributions -->

        <div class="card">

            <div class="section-head">

                <div>

                    <h3>Member Contributions</h3>

                    <p>
                        ${contributions.length}
                        contribution records
                    </p>

                </div>

            </div>


<div class="table-wrap">

<table class="table">

<thead>

<tr>
    <th>Member</th>
    <th>Group</th>
    <th>Round</th>
    <th>Expected</th>
    <th>Paid</th>
    <th>Due Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>

</thead>


<tbody>

${contributions.map(c => `

    <tr>

        <td>

            <div class="member-cell">

                <div class="avatar">
                    ${avatar(c.full_name)}
                </div>

                <strong>
                    ${c.full_name}
                </strong>

            </div>

        </td>


        <td>
            ${c.group_name}
        </td>


        <td>
            Round ${c.round_number}
        </td>


        <td class="amount">
            ${money(c.expected_amount)}
        </td>


        <td class="amount">
            ${money(c.paid_amount)}
        </td>


        <td>
            ${c.due_date}
        </td>


        <td>

            ${badge(
                c.status === "paid"
                    ? "Paid"
                    : c.status === "partial"
                        ? "Partial"
                        : c.status === "overdue"
                            ? "Overdue"
                            : "Pending"
            )}

        </td>


     <td>
        <button
            class="btn btn-light"
            onclick="goToPayments(${c.id})">
            View Payments
        </button>
</td>

    </tr>

`).join("")}

</tbody>

</table>

</div>

</div>

`;
}

function goToPayments(contributionId) {

    window.selectedContributionId = contributionId;

    showPage("payments");

}

function rotation() {
    content.innerHTML = `
        <div class="card">
            <p>Loading rotation...</p>
        </div>
    `;

    loadRotation();
}

async function loadRotation() {
    try {
        const response = await fetch("Rotation/get_turns.php");

        const turns = await response.json();

        if (!response.ok || turns.success === false) {
            throw new Error(turns.message || "Failed to load rotation.");
        }

        renderRotation(turns);

    } catch (error) {

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load rotation: ${error.message}
                </p>
            </div>
        `;

        console.error(error);
    }
}


function renderRotation(turns) {

    if (!turns || turns.length === 0) {

        content.innerHTML = `
            <div class="card">
                <h3>Rotation / Turns</h3>
                <p>No rotation turns found.</p>
            </div>
        `;

        return;
    }

    window.rotationData = turns;

    content.innerHTML = `
        <div class="card">

    <div class="card-header">
        <div>
            <h3>Rotation / Turns</h3>
            <p>Manage the order of members receiving the Mchezo.</p>
        </div>

        <div style="display:flex; gap:8px; align-items:center;">
            <select id="rotationGroupSelect" class="search">
                <option value="">Select Mchezo Group</option>
            </select>

            <button
                id="generateRotationBtn"
                class="btn btn-primary"
                type="button">
                ＋ Generate Rotation
            </button>
        </div>
    </div>

            <div class="rotation-table-wrapper">

           <table class="rotation-table">

<thead>
    <tr>
        <th>Turn</th>
        <th>Round</th>
        <th>Member</th>
        <th>Group</th>
        <th>Status</th>
        <th>Received Date</th>
        <th>Action</th>
    </tr>
</thead>

<tbody>

    ${turns.map(turn => `

        <tr>

            <td>
                ${turn.turn_number}
            </td>

            <td>
                Round ${turn.round_number}
            </td>

            <td class="member-name">
                ${turn.full_name}
            </td>

            <td class="group-name">
                ${turn.group_name}
            </td>

            <td>
                <span class="rotation-status ${turn.status}">
                    ${turn.status}
                </span>
            </td>

            <td>
                ${turn.received_date || "-"}
            </td>

            <td>

                ${
                    turn.status === "upcoming"

                    ?

                    `
                    <button
                        class="rotation-action-btn start"
                        onclick="changeTurnStatus(${turn.id}, 'current')">
                        Start Turn
                    </button>
                    `

                    :

                    turn.status === "current"

                    ?

                    `
                    <button
                        class="rotation-action-btn complete"
                        onclick="changeTurnStatus(${turn.id}, 'completed')">
                        Complete Turn
                    </button>
                    `

                    :

                    `
                    <span class="rotation-completed">
                        Completed
                    </span>
                    `
                }

            </td>

        </tr>

    `).join("")}

</tbody>

</table>

</div>

</div>
`;

loadRotationGroups();

const generateButton = document.getElementById("generateRotationBtn");

if (generateButton) {
    generateButton.addEventListener("click", generateRotation);
}

}

async function loadRotationGroups() {

    const select = document.getElementById("rotationGroupSelect");

    if (!select) return;

    try {

        const response = await fetch("Mchezo/get_groups.php");

        const data = await response.json();

        if (!response.ok) {
            throw new Error("Failed to load Mchezo groups.");
        }

        const groups = Array.isArray(data)
            ? data
            : data.groups || [];

        select.innerHTML = `
            <option value="">Select Mchezo Group</option>

            ${groups.map(group => `
                <option value="${group.id}">
                    ${escapeHtml(group.group_name)}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">Failed to load groups</option>
        `;
    }
}

async function generateRotation() {

    const select = document.getElementById("rotationGroupSelect");

    if (!select || !select.value) {
        alert("Please select a Mchezo group.");
        return;
    }

    const formData = new FormData();

    formData.append("group_id", select.value);

    try {

        const response = await fetch(
            "Rotation/generate_turns.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to generate rotation."
            );
        }

        alert(data.message);

        // Reload rotation from MySQL
        loadRotation();

    } catch (error) {

        alert(error.message);

        console.error(error);
    }
}

async function changeTurnStatus(turnId, status) {

    try {

        const formData = new FormData();

        formData.append("turn_id", turnId);
        formData.append("status", status);

        const response = await fetch(
            "Rotation/update_turn.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to update turn."
            );
        }

        alert(data.message);

        // Reload rotation from MySQL
        loadRotation();

    } catch (error) {

        alert(error.message);

        console.error(error);
    }
}

function payments() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Payments</h3>
                    <p>Manage payment transactions.</p>
                </div>

                <button
                    class="btn btn-primary"
                    onclick="openPaymentModal()">
                    Record Payment
                </button>

            </div>

            <div id="paymentsContent">
                <p>Loading payments...</p>
            </div>

        </div>
    `;

    loadPayments();
}


async function loadPayments() {

    try {

        const url = window.selectedContributionId
    ? `Payments/get_payments.php?contribution_id=${window.selectedContributionId}`
    : "Payments/get_payments.php";

const response = await fetch(url);

        const payments = await response.json();

        if (!response.ok || payments.success === false) {
            throw new Error(
                payments.message || "Failed to load payments."
            );
        }

        renderPayments(payments);

    } catch (error) {

        content.innerHTML = `
            <div class="card">
                <p style="color:red;">
                    Failed to load payments: ${error.message}
                </p>
            </div>
        `;

        console.error(error);
    }
}

function renderPayments(payments) {

    const container = document.getElementById("paymentsContent");

    if (!container) return;

    const filteredContribution =
    window.selectedContributionId || null;

if (!payments || payments.length === 0) {

    container.innerHTML = `
        <div class="card">

            <p>
                ${
                    filteredContribution
                        ? "No payments found for this contribution."
                        : "No payment records found."
                }
            </p>

        </div>
    `;

    return;
}

    window.paymentsData = payments;

    const totalPayments = payments.reduce(
        (total, payment) =>
            total + Number(payment.amount),
        0
    );

    container.innerHTML = `

    <div class="card">

    <div class="section-head">

        <div>
            <h3>
                ${
                    filteredContribution
                        ? "Contribution Payments"
                        : "All Payments"
                }
            </h3>

            <p>
                ${
                    filteredContribution
                        ? `Payments for Contribution #${filteredContribution}`
                        : "All recorded payments"
                }
            </p>
        </div>

        ${
            filteredContribution
                ? `
                    <button
                        class="btn btn-light"
                        onclick="clearPaymentFilter()"
                    >
                        View All Payments
                    </button>
                `
                : ""
        }

    </div>

</div>

<div style="height:18px"></div>

        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Payments</span>
                    <strong>${payments.length}</strong>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Amount</span>
                    <strong>
                        ${totalPayments.toLocaleString()}
                    </strong>
                </div>
            </div>

        </div>

        <div class="payment-table-wrapper">

            <table class="payment-table">

<thead>
    <tr>
        <th>Member</th>
        <th>Group</th>
        <th>Round</th>
        <th>Amount</th>
        <th>Method</th>
        <th>Reference</th>
        <th>Status</th>
        <th>Date</th>
    </tr>
</thead>

<tbody>

    ${payments.map(payment => `

        <tr>

            <td>${payment.full_name}</td>

            <td>${payment.group_name}</td>

            <td>
                Round ${payment.round_number}
            </td>

            <td>
                ${Number(payment.amount).toLocaleString()}
            </td>

            <td>
                ${payment.payment_method}
            </td>

            <td>
                ${payment.reference || "-"}
            </td>

            <td>
                <span class="
                    payment-status
                    ${payment.status}
                ">
                    ${payment.status}
                </span>
            </td>

            <td>
                ${payment.payment_date}
            </td>

        </tr>

    `).join("")}

</tbody>

</table>

</div>
`;
}

function clearPaymentFilter() {

    window.selectedContributionId = null;

    loadPayments();

}

async function openPaymentModal() {

    try {

        const response = await fetch(
            "Contributions/get_contributions.php"
        );

        const contributions = await response.json();

        if (!response.ok || contributions.success === false) {
            throw new Error(
                contributions.message ||
                "Failed to load contributions."
            );
        }

        const unpaid = contributions.filter(c =>
            Number(c.expected_amount) >
            Number(c.paid_amount)
        );

        if (unpaid.length === 0) {
            toast("There are no unpaid contributions.");
            return;
        }

        document.getElementById("modalTitle").textContent =
            "Record Payment";

        document.getElementById("modalBody").innerHTML = `

            <div class="form-grid">

                <!-- Contribution -->
                <div class="field">

                    <label for="paymentContribution">
                        Contribution
                    </label>

                    <select
                        id="paymentContribution"
                        required>

                        <option value="">
                            Select contribution
                        </option>

                        ${unpaid.map(c => {

                            const remaining =
                                Number(c.expected_amount) -
                                Number(c.paid_amount);

                            return `
                                <option value="${c.id}">
                                    ${c.full_name}
                                    - Round ${c.round_number}
                                    - Remaining:
                                    ${money(remaining)}
                                </option>
                            `;

                        }).join("")}

                    </select>

                </div>


                <!-- Amount -->
                <div class="field">

                    <label for="paymentAmount">
                        Amount
                    </label>

                    <input
                        type="number"
                        id="paymentAmount"
                        min="1"
                        step="0.01"
                        placeholder="Enter payment amount"
                        required>

                </div>


                <!-- Payment Method -->
                <div class="field">

                    <label for="paymentMethod">
                        Payment Method
                    </label>

                    <select
                        id="paymentMethod"
                        required>

                        <option value="">
                            Select method
                        </option>

                        <option value="cash">
                            Cash
                        </option>

                        <option value="mobile_money">
                            Mobile Money
                        </option>

                        <option value="bank">
                            Bank
                        </option>

                        <option value="azampay">
                            AzamPay
                        </option>

                    </select>

                </div>


                <!-- AzamPay Details -->
                <div
                    id="azampayFields"
                    style="display:none; grid-column:1/-1;">

                    <div class="form-grid">

                        <div class="field">

                            <label for="azampayAccount">
                                Phone / Account Number
                            </label>

                            <input
                                type="text"
                                id="azampayAccount"
                                placeholder="e.g. 255712345678">

                        </div>


                        <div class="field">

                            <label for="azampayProvider">
                                Mobile Money Provider
                            </label>

                            <select id="azampayProvider">

                                <option value="">
                                    Select provider
                                </option>

                                <option value="Airtel">
                                    Airtel
                                </option>

                                <option value="Tigo">
                                    Tigo
                                </option>

                                <option value="Halopesa">
                                    Halopesa
                                </option>

                                <option value="Azampesa">
                                    Azampesa
                                </option>

                                <option value="Mpesa">
                                    M-Pesa
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- Reference -->
                <div class="field">

                    <label for="paymentReference">
                        Reference
                    </label>

                    <input
                        type="text"
                        id="paymentReference"
                        placeholder="Optional reference">

                </div>

            </div>


            <div class="actions">

                <button
                    type="button"
                    class="btn btn-light"
                    onclick="closeModal()">

                    Cancel

                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="savePaymentBtn"
                    onclick="savePayment()">

                    Save Payment

                </button>

            </div>
        `;


        /*
         * Show/hide AzamPay fields
         */
        document
            .getElementById("paymentMethod")
            .addEventListener("change", function () {

                const azampayFields =
                    document.getElementById("azampayFields");

                const saveButton =
                    document.getElementById("savePaymentBtn");

                if (this.value === "azampay") {

                    azampayFields.style.display = "block";

                    saveButton.textContent =
                        "Pay with AzamPay";

                } else {

                    azampayFields.style.display = "none";

                    saveButton.textContent =
                        "Save Payment";

                }

            });


        document
            .getElementById("modal")
            .classList.remove("hidden");

    } catch (error) {

        console.error(error);

        toast(error.message);

    }
}


async function savePayment() {

    const contributionId =
        document.getElementById(
            "paymentContribution"
        ).value;

    const amount =
        document.getElementById(
            "paymentAmount"
        ).value;

    const paymentMethod =
        document.getElementById(
            "paymentMethod"
        ).value;

    const reference =
        document.getElementById(
            "paymentReference"
        ).value.trim();


    if (!contributionId) {
        toast("Please select a contribution.");
        return;
    }


    if (!amount || Number(amount) <= 0) {
        toast("Please enter a valid payment amount.");
        return;
    }


    if (!paymentMethod) {
        toast("Please select a payment method.");
        return;
    }


    const formData = new FormData();

    formData.append(
        "contribution_id",
        contributionId
    );

    formData.append(
        "amount",
        amount
    );

    formData.append(
        "payment_method",
        paymentMethod
    );

    formData.append(
        "reference",
        reference
    );


    try {

        const response = await fetch(
            "Payments/add_payment.php",
            {
                method: "POST",
                body: formData
            }
        );

        const result = await response.json();


        if (!response.ok || !result.success) {

            throw new Error(
                result.message ||
                "Failed to record payment."
            );
        }


        closeModal();

        toast(result.message);

        loadPayments();

    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}


async function meetings() {

    content.innerHTML = `
        <div class="card">
            <p>Loading meetings...</p>
        </div>
    `;

    try {

        const response = await fetch("Meetings/get_meetings.php");

        if (!response.ok) {
            throw new Error("Failed to load meetings");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Failed to load meetings");
        }

        renderMeetings(data.meetings);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">

                <h3>Meetings</h3>

                <p style="color:red;">
                    Failed to load meetings from database.
                </p>

            </div>
        `;
    }
}


async function payFine(fineId) {

    const confirmed = confirm(
        "Are you sure you want to mark this fine as paid?"
    );

    if (!confirmed) {
        return;
    }

    const formData = new FormData();

    formData.append("fine_id", fineId);

    try {

        const response = await fetch(
            "Fines/pay_fine.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to mark fine as paid."
            );
        }

        toast("Fine marked as paid successfully.");

        // Reload fines from database
        fines();

    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

function renderMeetings(meetings) {

    if (!meetings || meetings.length === 0) {

        content.innerHTML = `
            <div class="card">

                <div class="section-head">

                    <div>
                        <h3>Meetings</h3>

                        <p>
                            No meetings have been created yet.
                        </p>
                    </div>

                    <button
                        class="btn btn-primary"
                        onclick="openModal('Create Meeting')"
                    >
                        ＋ New Meeting
                    </button>

                </div>

                <div class="empty-state">
                    <p>
                        Create your first Mchezo meeting.
                    </p>
                </div>

            </div>
        `;

        return;
    }


    content.innerHTML = `

        <div class="card">

            <div class="section-head">

                <div>

                    <h3>Meetings</h3>

                    <p>
                        Schedule meetings and record member attendance.
                    </p>

                </div>

                <button
                    class="btn btn-primary"
                    onclick="openModal('Create Meeting')"
                >
                    ＋ New Meeting
                </button>

            </div>


<div class="grid three">

    ${meetings.map(meeting => `

        <div
            class="card"
            style="box-shadow:none"
        >

            <strong style="font-size:13px">
                ${escapeHtml(meeting.title)}
            </strong>


            <p
                class="muted"
                style="font-size:11px"
            >
                ${escapeHtml(meeting.meeting_date)}
            </p>


            <p
                class="muted"
                style="font-size:11px"
            >
                Group:
                ${escapeHtml(meeting.group_name)}
            </p>


            ${
                meeting.location
                ? `
                    <p
                        class="muted"
                        style="font-size:11px"
                    >
                        Location:
                        ${escapeHtml(meeting.location)}
                    </p>
                `
                : ""
            }


            <div>
                ${badge("Scheduled")}
            </div>


            <div class="actions">

                <button class="btn btn-light" onclick="openAttendance(${meeting.id})">
                            View Attendance
            </button>

            </div>

        </div>

    `).join("")}

</div>

</div>

    `;
}

async function openAttendance(meetingId) {

    openModal("Attendance");

    const body = document.getElementById("modalBody");

    body.innerHTML = `
        <div class="card">
            <p>Loading attendance...</p>
        </div>
    `;

    try {

        const response = await fetch(
            `Meetings/get_attendance.php?meeting_id=${meetingId}`
        );

        if (!response.ok) {
            throw new Error("Failed to load attendance");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.message || "Failed to load attendance"
            );
        }

        renderAttendance(data);

    } catch (error) {

        console.error(error);

        body.innerHTML = `
            <div class="card">
                <h3>Attendance</h3>

                <p style="color:red;">
                    Failed to load attendance.
                </p>

                <div class="actions">
                    <button
                        class="btn btn-light"
                        onclick="closeModal()"
                    >
                        Close
                    </button>
                </div>
            </div>
        `;
    }
}

function renderAttendance(data) {

    const body = document.getElementById("modalBody");

    const meeting = data.meeting;
    const members = data.members || [];

    const totalMembers = members.length;

    const presentCount = members.filter(
        member => member.status === "present"
    ).length;

    const absentCount = members.filter(
        member => member.status === "absent"
    ).length;

    const notRecordedCount = members.filter(
        member => !member.status
    ).length;

    body.innerHTML = `

        <div>

            <!-- Meeting information -->

            <div style="margin-bottom:20px;">

                <h3>
                    ${escapeHtml(meeting.title)}
                </h3>

                <p class="muted">
                    ${escapeHtml(meeting.meeting_date)}
                    ·
                    ${escapeHtml(meeting.group_name)}
                </p>

            </div>


            <!-- Attendance summary -->

            <div class="grid three" style="margin-bottom:20px;">

                <div class="card" style="box-shadow:none;">
                    <strong>Total Members</strong>

                    <h2>
                        ${totalMembers}
                    </h2>
                </div>


                <div class="card" style="box-shadow:none;">
                    <strong>Present</strong>

                    <h2>
                        ${presentCount}
                    </h2>
                </div>


                <div class="card" style="box-shadow:none;">
                    <strong>Absent</strong>

                    <h2>
                        ${absentCount}
                    </h2>
                </div>

            </div>


            <!-- Attendance table -->

            ${
                members.length === 0

                ?

                `
                <div class="empty-state">

                    <p>
                        No active members found
                        in this Mchezo group.
                    </p>

                </div>
                `

                :

                `

                <div class="table-wrap">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>Member</th>

                                <th>Phone</th>

                                <th>Status</th>

                                <th>Remarks</th>

                            </tr>

                        </thead>


                        <tbody>

                            ${members.map(member => `

                                <tr>

                                    <td>
                                        <strong>
                                            ${escapeHtml(
                                                member.full_name
                                            )}
                                        </strong>
                                    </td>


                                    <td>
                                        ${escapeHtml(
                                            member.phone
                                        )}
                                    </td>


                                    <td>

                                        ${
                                            member.status === "present"

                                            ?

                                            badge("Present")

                                            :

                                            member.status === "absent"

                                            ?

                                            badge("Absent")

                                            :

                                            badge("Not Recorded")
                                        }

                                    </td>


                                    <td>

                                        ${
                                            member.remarks
                                            ?
                                            escapeHtml(member.remarks)
                                            :
                                            "—"
                                        }

                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>

                `

            }


            <!-- Buttons -->

            <div
                class="actions"
                style="margin-top:20px;"
            >

                <button
                    class="btn btn-light"
                    onclick="closeModal()"
                >
                    Close
                </button>


                <button
                    class="btn btn-primary"
                    onclick="editAttendance(${meeting.id})"
                >
                    Edit Attendance
                </button>

            </div>

        </div>
    `;
}

function editAttendance(meetingId) {

    loadAttendanceForm(meetingId);

}

async function loadAttendanceForm(meetingId) {

    const body = document.getElementById("modalBody");

    body.innerHTML = `
        <div class="card">
            <p>Loading attendance form...</p>
        </div>
    `;

    try {

        const response = await fetch(
            `Meetings/get_attendance.php?meeting_id=${meetingId}`
        );

        if (!response.ok) {
            throw new Error("Failed to load attendance");
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.message || "Failed to load attendance"
            );
        }

        renderAttendanceForm(data);

    } catch (error) {

        console.error(error);

        body.innerHTML = `
            <div class="card">

                <h3>Attendance</h3>

                <p style="color:red;">
                    Failed to load attendance form.
                </p>

            </div>
        `;
    }
}

function renderAttendanceForm(data) {

    const body = document.getElementById("modalBody");

    const meeting = data.meeting;
    const members = data.members || [];

    body.innerHTML = `
        <div>

            <div style="margin-bottom:20px;">
                <h3>Edit Attendance</h3>

                <p class="muted">
                    ${escapeHtml(meeting.title)}
                    ·
                    ${escapeHtml(meeting.meeting_date)}
                    ·
                    ${escapeHtml(meeting.group_name)}
                </p>
            </div>

            ${
                members.length === 0

                ?

                `
                <div class="empty-state">
                    <p>No active members found.</p>
                </div>
                `

                :

                `
                <div class="table-wrap">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Attendance</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>

                            ${members.map(member => `

                                <tr>

                                    <td>
                                        <strong>
                                            ${escapeHtml(member.full_name)}
                                        </strong>
                                    </td>

                                    <td>

                                        <select
                                            class="attendance-status"
                                            data-member-id="${member.member_id}"
                                        >

                                            <option
                                                value=""
                                                ${!member.status ? "selected" : ""}
                                            >
                                                Not Recorded
                                            </option>

                                            <option
                                                value="present"
                                                ${member.status === "present" ? "selected" : ""}
                                            >
                                                Present
                                            </option>

                                            <option
                                                value="absent"
                                                ${member.status === "absent" ? "selected" : ""}
                                            >
                                                Absent
                                            </option>

                                        </select>

                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            class="attendance-remarks"
                                            data-member-id="${member.member_id}"
                                            value="${escapeHtml(member.remarks || "")}"
                                            placeholder="Optional"
                                        >

                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>
                `
            }

            <div
                class="actions"
                style="margin-top:20px;"
            >

                <button
                    class="btn btn-light"
                    onclick="openAttendance(${meeting.id})"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    onclick="saveAttendance(${meeting.id})"
                >
                    Save Attendance
                </button>

            </div>

        </div>
    `;
}


async function saveAttendance(meetingId) {

    const statusElements = document.querySelectorAll(".attendance-status");

    const attendance = [];

    statusElements.forEach(select => {

        const memberId = select.dataset.memberId;

        const remarksInput = document.querySelector(
            `.attendance-remarks[data-member-id="${memberId}"]`
        );

        attendance.push({
            member_id: memberId,
            status: select.value,
            remarks: remarksInput
                ? remarksInput.value.trim()
                : ""
        });

    });


    // Make sure every member has been marked
    const incomplete = attendance.some(
        record => !record.status
    );

    if (incomplete) {

        toast("Please mark every member as Present or Absent.");

        return;
    }


    const formData = new FormData();

    formData.append(
        "meeting_id",
        meetingId
    );

    formData.append(
        "attendance",
        JSON.stringify(attendance)
    );


    try {

        const response = await fetch(
            "Meetings/save_attendance.php",
            {
                method: "POST",
                body: formData
            }
        );


        const data = await response.json();


        if (!data.success) {

            toast(
                data.message ||
                "Failed to save attendance."
            );

            return;
        }


        toast("Attendance saved successfully.");

        closeModal();


    } catch (error) {

        console.error(error);

        toast("Failed to save attendance.");

    }
}


async function fines() {

    simplePage(
        "Fines",
        "Record fines according to your group rules.",
        `
        <div class="card">
            <p>Loading fines...</p>
        </div>
        `,
        `
        <button
            class="btn btn-primary"
            onclick="openModal('Record Fine')"
        >
            ＋ Record Fine
        </button>
        `
    );

    try {

        const response = await fetch("Fines/get_fines.php");

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to load fines."
            );
        }

        renderFines(data.fines);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">

                <h3>Fines</h3>

                <p style="color:red;">
                    Failed to load fines: ${escapeHtml(error.message)}
                </p>

            </div>
        `;
    }
}


function renderFines(fines) {

    if (!fines || fines.length === 0) {

        simplePage(
            "Fines",
            "Record fines according to your group rules.",
            `
            <div class="empty-state">

                <p>
                    No fines have been recorded yet.
                </p>

            </div>
            `,
            `
            <button
                class="btn btn-primary"
                onclick="openModal('Record Fine')"
            >
                ＋ Record Fine
            </button>
            `
        );

        return;
    }


    simplePage(
        "Fines",
        "Record fines according to your group rules.",
        `
        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Member</th>
                        <th>Group</th>
                        <th>Meeting</th>
                        <th>Reason</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    ${fines.map(fine => `

                        <tr>

                            <td>
                                <strong>
                                    ${escapeHtml(fine.full_name)}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(fine.group_name)}
                            </td>

                            <td>
                                ${
                                    fine.meeting_title
                                    ? escapeHtml(fine.meeting_title)
                                    : "—"
                                }
                            </td>

                            <td>
                                ${escapeHtml(fine.reason)}
                            </td>

                            <td class="amount">
                                ${money(fine.amount)}
                            </td>

                            <td>
                                ${escapeHtml(fine.created_at)}
                            </td>

                            <td>
                                ${badge(fine.status)}
                            </td>

                            <td>

                                ${
                                    fine.status === "unpaid"

                                    ?

                                    `
                                    <button
                                        class="btn btn-light"
                                        onclick="payFine(${fine.id})"
                                    >
                                        Mark Paid
                                    </button>
                                    `

                                    :

                                    `
                                    <span class="muted">
                                        Paid
                                    </span>
                                    `
                                }

                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>
        `,
        `
        <button
            class="btn btn-primary"
            onclick="openModal('Record Fine')"
        >
            ＋ Record Fine
        </button>
        `
    );
}


async function transactions() {

    simplePage(
        "Transaction Ledger",
        "A consolidated view of money entering or leaving the Mchezo record.",
        `
        <div class="card">
            <p>Loading transactions...</p>
        </div>
        `
    );

    try {

        const response = await fetch(
            "Transactions/get_transactions.php"
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Failed to load transactions."
            );
        }

        renderTransactions(data.transactions);

    } catch (error) {

        console.error(error);

        content.innerHTML = `
            <div class="card">

                <h3>Transaction Ledger</h3>

                <p style="color:red;">
                    Failed to load transactions:
                    ${escapeHtml(error.message)}
                </p>

            </div>
        `;
    }
}

function transactions() {

    simplePage(
        "Transaction Ledger",
        "A consolidated view of money entering or leaving the Mchezo record.",
        `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Transaction Ledger</h3>
                    <p>Record and view Mchezo financial transactions.</p>
                </div>

                <button
                    id="recordTransactionBtn"
                    class="btn btn-primary"
                    type="button"
                >
                    ＋ Record Transaction
                </button>

            </div>

            <div id="transactionContent">
                <p class="muted">Loading transactions...</p>
            </div>

        </div>
        `
    );

    // Attach the button event AFTER the HTML has been created
    const button = document.getElementById("recordTransactionBtn");

    if (button) {
        button.addEventListener("click", function () {
            openModal("Record Transaction");
        });
    }

    loadTransactions();
}

function renderTransactions(transactions) {

    const container =
        document.getElementById("transactionContent");

    if (!container) return;

    if (transactions.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No transactions yet</h3>

                <p>
                    No financial transactions have been recorded.
                </p>

            </div>
        `;

        return;
    }

    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Group</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>

                <tbody>

                    ${transactions.map(transaction => `

                        <tr>

                            <td>
                                ${escapeHtml(
                                    transaction.transaction_date || ""
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        transaction.group_name || ""
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.description || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.type || ""
                                )}
                            </td>

                            <td class="amount">
                                ${money(
                                    Number(transaction.amount || 0)
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.recorded_by_name || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}

async function loadTransactions() {

    const container =
        document.getElementById("transactionContent");

    if (!container) return;

    try {

        const response = await fetch(
            "Transactions/get_transactions.php"
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Failed to load transactions."
            );
        }

        renderTransactions(data.transactions || []);

    } catch (error) {

        console.error(error);

        container.innerHTML = `
            <div class="empty-state">
                <p style="color:red;">
                    Failed to load transactions:
                    ${escapeHtml(error.message)}
                </p>
            </div>
        `;
    }
}

async function loadTransactionGroups() {

    const select =
        document.getElementById("transactionGroupId");

    if (!select) return;

    try {

        const response =
            await fetch("Mchezo/get_groups.php");

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error("Failed to load groups.");
        }

        const groups =
            Array.isArray(data)
                ? data
                : data.groups || [];

        select.innerHTML = `
            <option value="">
                Select group
            </option>

            ${groups.map(group => `
                <option value="${group.id}">
                    ${escapeHtml(group.group_name)}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Failed to load groups
            </option>
        `;
    }
}

function renderTransactions(transactions) {

    const container =
        document.getElementById("transactionContent");

    if (!container) return;

    if (transactions.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No transactions yet</h3>

                <p>
                    No financial transactions have been recorded.
                </p>

                <button
                    class="btn btn-primary"
                    onclick="openModal('Record Transaction')"
                >
                    ＋ Record Transaction
                </button>

            </div>
        `;

        return;
    }

    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Group</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>

                <tbody>

                    ${transactions.map(transaction => `

                        <tr>

                            <td>
                                ${escapeHtml(
                                    transaction.transaction_date || ""
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        transaction.group_name || ""
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.description || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.type || ""
                                )}
                            </td>

                            <td class="amount">
                                ${money(
                                    Number(transaction.amount || 0)
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    transaction.recorded_by_name || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}

async function saveTransaction() {

    const groupId =
        document.getElementById("transactionGroupId").value;

    const type =
        document.getElementById("transactionType").value;

    const amount =
        document.getElementById("transactionAmount").value;

    const transactionDate =
        document.getElementById("transactionDate").value;

    const description =
        document.getElementById("transactionDescription").value.trim();

    if (!groupId) {
        toast("Please select a Mchezo group.");
        return;
    }

    if (!type) {
        toast("Please select a transaction type.");
        return;
    }

    if (!amount || Number(amount) <= 0) {
        toast("Please enter a valid amount.");
        return;
    }

    const formData = new FormData();

    formData.append("group_id", groupId);
    formData.append("type", type);
    formData.append("amount", amount);
    formData.append("transaction_date", transactionDate);
    formData.append("description", description);

    try {

        const response = await fetch(
            "Transactions/add_transaction.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Failed to record transaction."
            );
        }

        toast("Transaction recorded successfully.");

        closeModal();

        transactions();

    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

async function showTransactionReport() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Transaction Report</h3>
                    <p>
                        Detailed financial transactions recorded in KIJUMBE.
                    </p>
                </div>

                <button
                    class="btn btn-light"
                    type="button"
                    id="backToReportsBtn"
                >
                    ← Back to Reports
                </button>

            </div>

            <div id="transactionReportContent">

                <p class="muted">
                    Loading transaction report...
                </p>

            </div>

        </div>
    `;

    document
        .getElementById("backToReportsBtn")
        .addEventListener("click", function () {
            reports();
        });

    try {

        const response = await fetch(
            "Transactions/get_transactions.php"
        );

        const data = await response.json();

        console.log("Transaction report:", data);

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Failed to load transaction report."
            );
        }

        renderTransactionReport(
            data.transactions || []
        );

    } catch (error) {

        console.error(error);

        const container =
            document.getElementById(
                "transactionReportContent"
            );

        if (container) {

            container.innerHTML = `
                <div class="empty-state">

                    <h3>Failed to Load Report</h3>

                    <p style="color:red;">
                        ${escapeHtml(error.message)}
                    </p>

                </div>
            `;
        }
    }
}

async function showTransactionReport() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Transaction Report</h3>

                    <p>
                        Detailed financial transactions recorded in KIJUMBE.
                    </p>
                </div>

                <div style="display:flex;gap:8px;">

                    <button
                        class="btn btn-light"
                        type="button"
                        id="backToReportsBtn"
                    >
                        ← Back to Reports
                    </button>

                    <button
                        class="btn btn-primary"
                        type="button"
                        onclick="printReport()"
                    >
                        🖨 Print Report
                    </button>

                </div>

            </div>

            <div id="transactionReportContent">

                <p class="muted">
                    Loading transaction report...
                </p>

            </div>

        </div>
    `;

    document
        .getElementById("backToReportsBtn")
        .addEventListener("click", function () {
            reports();
        });

    try {

        const response = await fetch(
            "Transactions/get_transactions.php"
        );

        const data = await response.json();

        console.log("Transaction report:", data);

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to load transaction report."
            );
        }

        renderTransactionReport(
            data.transactions || []
        );

    } catch (error) {

        console.error(error);

        const container =
            document.getElementById(
                "transactionReportContent"
            );

        if (container) {

            container.innerHTML = `
                <div class="empty-state">

                    <h3>Failed to Load Report</h3>

                    <p style="color:red;">
                        ${escapeHtml(error.message)}
                    </p>

                </div>
            `;
        }
    }
}

function renderTransactionReport(transactions) {

    const container =
        document.getElementById(
            "transactionReportContent"
        );

    if (!container) return;

    if (transactions.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No Transactions</h3>

                <p>
                    There are no financial transactions
                    recorded yet.
                </p>

            </div>
        `;

        return;
    }

    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Group</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                    </tr>

                </thead>

                <tbody>

                    ${transactions.map(t => `

                        <tr>

                            <td>
                                ${escapeHtml(
                                    t.transaction_date || "-"
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        t.group_name || "-"
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    t.type || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    t.description || "-"
                                )}
                            </td>

                            <td class="amount">
                                ${money(
                                    Number(t.amount || 0)
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    t.recorded_by_name || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>

        <div
            style="
                margin-top:15px;
                font-size:13px;
            "
        >

            <strong>Total Records:</strong>
            ${transactions.length}

        </div>

    `;
}

function reports() {

    content.innerHTML = `

        <div class="grid three">

 ${stat(
    "Total Contributions",
    `<span id="reportTotalContributions">Loading...</span>`,
    "Collected from rounds",
    "▤"
)}

${stat(
    "Total Fines",
    `<span id="reportTotalFines">Loading...</span>`,
    "Recorded group fines",
    "!"
)}

${stat(
    "Completed Turns",
    `<span id="reportCompletedTurns">Loading...</span>`,
    "Rotation progress",
    "↻"
)}

        </div>

        <div style="height:18px"></div>

        <div class="grid two">

            ${card(
                "Collection Performance",
                `
                <div id="collectionPerformance">
                    <p class="muted">
                        Loading collection data...
                    </p>
                </div>
                `
            )}

            ${card(
                "Reports",
                `
                <p
                    class="muted"
                    style="font-size:12px;line-height:1.6"
                >
                    Generate summaries for contributions,
                    payments, attendance, fines and transactions.
                </p>

                <div
                    style="
                        display:flex;
                        gap:8px;
                        flex-wrap:wrap;
                    "
                >

                    <button
                        class="btn btn-primary"
                        type="button"
                        onclick="showContributionReport()"
                    >
                        Contribution Report
                    </button>

                    <button
                        class="btn btn-light"
                        type="button"
                        onclick="showTransactionReport()"
                    >
                        Transaction Report
                    </button>

                    <button
                        class="btn btn-light"
                        type="button"
                        onclick="showMemberReport()"
                    >
                        Member Report
                    </button>

                </div>
                `
            )}

        </div>
    `;

    loadReports();

}

function auditLogs() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Audit Logs</h3>

                    <p>
                        Track important actions performed in KIJUMBE.
                    </p>
                </div>

            </div>

            <div id="auditLogsContent">

                <p class="muted">
                    Loading audit logs...
                </p>

            </div>

        </div>
    `;

    loadAuditLogs();
}

async function loadAuditLogs() {

    try {

        const response = await fetch(
            "Audit/get_logs.php"
        );

        const data = await response.json();

        console.log("Audit logs:", data);

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to load audit logs."
            );
        }

        renderAuditLogs(
            data.logs || []
        );

    } catch (error) {

        console.error(error);

        const container =
            document.getElementById(
                "auditLogsContent"
            );

        if (container) {

            container.innerHTML = `
                <div class="empty-state">

                    <h3>Failed to Load Audit Logs</h3>

                    <p style="color:red;">
                        ${escapeHtml(error.message)}
                    </p>

                </div>
            `;
        }
    }
}

function renderAuditLogs(logs) {

    const container =
        document.getElementById(
            "auditLogsContent"
        );

    if (!container) return;


    if (logs.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No Audit Logs</h3>

                <p>
                    No system activities have been recorded yet.
                </p>

            </div>
        `;

        return;
    }


    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>

                </thead>

                <tbody>

                    ${logs.map(log => `

                        <tr>

                            <td>
                                ${escapeHtml(
                                    log.created_at || "-"
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        log.user_name || "Unknown User"
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    log.role || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    log.action || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    log.details || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>

        <div
            style="
                margin-top:15px;
                font-size:13px;
            "
        >

            <strong>
                Total Logs:
            </strong>

            ${logs.length}

        </div>

    `;
}

async function showContributionReport() {

    try {

        const response = await fetch(
            "Reports/get_contribution_report.php"
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Failed to load contribution report."
            );
        }

        const contributions =
            data.contributions || [];

        let html = "";

        if (contributions.length === 0) {

            html = `
                <div class="empty-state">
                    <h3>No Contribution Records</h3>
                    <p>
                        There are no contribution records
                        available yet.
                    </p>
                </div>
            `;

        } else {

            html = `
                <div class="table-wrap">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Group</th>
                                <th>Round</th>
                                <th>Expected</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            ${contributions.map(c => `

                                <tr>

                                    <td>
                                        <strong>
                                            ${escapeHtml(
                                                c.full_name
                                            )}
                                        </strong>
                                    </td>

                                    <td>
                                        ${escapeHtml(
                                            c.group_name
                                        )}
                                    </td>

                                    <td>
                                        Round ${c.round_number}
                                    </td>

                                    <td class="amount">
                                        ${money(
                                            Number(
                                                c.expected_amount || 0
                                            )
                                        )}
                                    </td>

                                    <td class="amount">
                                        ${money(
                                            Number(
                                                c.paid_amount || 0
                                            )
                                        )}
                                    </td>

                                    <td>
                                        ${escapeHtml(
                                            c.status
                                        )}
                                    </td>

                                    <td>
                                        ${escapeHtml(
                                            c.due_date || "-"
                                        )}
                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>
            `;
        }

        openReportModal(
            "Contribution Report",
            html
        );

    } catch (error) {

        console.error(error);

        toast(
            error.message ||
            "Failed to load contribution report."
        );
    }
}

function openReportModal(title, contentHtml) {

    const modal =
        document.getElementById("modal");

    const modalBody =
        document.getElementById("modalBody");

    if (!modal || !modalBody) {

        console.error(
            "Modal or modalBody was not found."
        );

        return;
    }

    modalBody.innerHTML = `

        <div>

            <div style="margin-bottom:20px;">

                <h3>
                    ${escapeHtml(title)}
                </h3>

                <p class="muted">
                    Generated from current KIJUMBE data.
                </p>

            </div>

            ${contentHtml}

            <div
                class="actions"
                style="margin-top:20px;"
            >

                <button
                    class="btn btn-light"
                    type="button"
                    onclick="closeModal()"
                >
                    Close
                </button>

            </div>

        </div>

    `;

    modal.classList.add("active");
}

async function showContributionReport() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Contribution Report</h3>
                    <p>
                        Detailed contribution records from KIJUMBE.
                    </p>
                </div>

            <div style="display:flex;gap:8px;">

                <div style="display:flex;gap:8px;">

            <button
                class="btn btn-light"
                type="button"
                id="backToReportsBtn"
            >
                ← Back to Reports
            </button>

            <button
                class="btn btn-primary"
                type="button"
                onclick="printReport()"
            >
                🖨 Print Report
            </button>

        </div>

</div>

            </div>

            <div id="contributionReportContent">

                <p class="muted">
                    Loading contribution report...
                </p>

            </div>

        </div>
    `;

    document
        .getElementById("backToReportsBtn")
        .addEventListener("click", function () {
            reports();
        });

    try {

        const response = await fetch(
            "Reports/get_contribution_report.php"
        );

        const data = await response.json();

        console.log("Contribution report:", data);

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Failed to load contribution report."
            );
        }

        renderContributionReport(
            data.contributions || []
        );

    } catch (error) {

        console.error(error);

        const reportContent =
            document.getElementById(
                "contributionReportContent"
            );

        if (reportContent) {

            reportContent.innerHTML = `
                <div class="empty-state">

                    <h3>Failed to Load Report</h3>

                    <p style="color:red;">
                        ${escapeHtml(error.message)}
                    </p>

                </div>
            `;
        }
    }
}

async function loadReports() {

    try {

        const response = await fetch(
            "Reports/get_reports.php"
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to load reports."
            );
        }

        updateReportSummary(data.summary);

        renderCollectionPerformance(
            data.rounds || []
        );

    } catch (error) {

        console.error(error);

        toast(
            error.message ||
            "Failed to load reports."
        );
    }
}

function updateReportSummary(summary) {

    const totalContributions =
        Number(summary.total_contributions || 0);

    const totalFines =
        Number(summary.total_fines || 0);

    const completedTurns =
        Number(summary.completed_turns || 0);

    const totalTurns =
        Number(summary.total_turns || 0);

    const percentage =
        totalTurns > 0
            ? Math.round(
                (completedTurns / totalTurns) * 100
            )
            : 0;


    const contributionElement =
        document.getElementById(
            "reportTotalContributions"
        );

    if (contributionElement) {
        contributionElement.innerHTML =
            money(totalContributions);
    }


    const finesElement =
        document.getElementById(
            "reportTotalFines"
        );

    if (finesElement) {
        finesElement.innerHTML =
            money(totalFines);
    }


    const turnsElement =
        document.getElementById(
            "reportCompletedTurns"
        );

    if (turnsElement) {

        turnsElement.innerHTML =
            `${completedTurns} / ${totalTurns}
             <small>(${percentage}%)</small>`;
    }
}

function renderCollectionPerformance(rounds) {

    const container =
        document.getElementById(
            "collectionPerformance"
        );

    if (!container) return;


    if (rounds.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No rounds available</h3>

                <p>
                    Collection performance will appear
                    when rounds are created.
                </p>

            </div>
        `;

        return;
    }


    container.innerHTML = rounds.map(round => {

        const expected =
            Number(round.expected_amount || 0);

        const collected =
            Number(round.collected_amount || 0);

        const percentage =
            expected > 0
                ? Math.min(
                    100,
                    Math.round(
                        (collected / expected) * 100
                    )
                  )
                : 0;


        return `

            <div
                style="
                    font-size:12px;
                    margin-bottom:8px;
                "
            >

                <strong>
                    ${escapeHtml(round.group_name)}
                </strong>

                · Round ${round.round_number}

                <span style="float:right">
                    ${money(collected)}
                    /
                    ${money(expected)}
                </span>

            </div>


            <div class="progress">

                <span
                    style="
                        width:${percentage}%;
                    "
                ></span>

            </div>

            <div
                style="
                    font-size:11px;
                    margin-top:4px;
                    margin-bottom:15px;
                "
            >
                ${percentage}% collected
            </div>

        `;

    }).join("");
}

function renderContributionReport(contributions) {

    const container =
        document.getElementById(
            "contributionReportContent"
        );

    if (!container) return;


    if (contributions.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No Contribution Records</h3>

                <p>
                    There are no contribution records
                    available yet.
                </p>

            </div>
        `;

        return;
    }


    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Member</th>
                        <th>Group</th>
                        <th>Round</th>
                        <th>Expected</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Paid Date</th>
                    </tr>

                </thead>

                <tbody>

                    ${contributions.map(c => `

                        <tr>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        c.full_name || "-"
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    c.group_name || "-"
                                )}
                            </td>

                            <td>
                                Round ${escapeHtml(
                                    String(c.round_number || "-")
                                )}
                            </td>

                            <td class="amount">
                                ${money(
                                    Number(
                                        c.expected_amount || 0
                                    )
                                )}
                            </td>

                            <td class="amount">
                                ${money(
                                    Number(
                                        c.paid_amount || 0
                                    )
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    c.status || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    c.due_date || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    c.paid_date || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>

        <div
            style="
                margin-top:15px;
                font-size:13px;
            "
        >
            <strong>
                Total Records:
            </strong>

            ${contributions.length}
        </div>

    `;
}

function showReportModal(title, html) {

    const modal =
        document.getElementById("modal");

    const modalBody =
        document.getElementById("modalBody");

    if (!modal || !modalBody) {

        console.error("Modal elements not found.");

        return;
    }

    modalBody.innerHTML = `

        <div>

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:20px;
                "
            >

                <div>
                    <h3>${escapeHtml(title)}</h3>

                    <p class="muted">
                        Generated from current KIJUMBE data.
                    </p>
                </div>

            </div>

            ${html}

            <div
                class="actions"
                style="margin-top:20px;"
            >

                <button
                    class="btn btn-light"
                    type="button"
                    onclick="closeModal()"
                >
                    Close
                </button>

            </div>

        </div>
    `;

    modal.classList.add("active");
}

async function showMemberReport() {

    content.innerHTML = `
        <div class="card">

            <div class="section-head">

                <div>
                    <h3>Member Report</h3>
                    <p>
                        List of members registered in KIJUMBE.
                    </p>
                </div>

        <div style="display:flex;gap:8px;">

            <button
                class="btn btn-light"
                type="button"
                id="backToReportsBtn"
            >
                ← Back to Reports
            </button>

            <button
                class="btn btn-primary"
                type="button"
                onclick="printReport()"
            >
                🖨 Print Report
            </button>

        </div>

            </div>

            <div id="memberReportContent">

                <p class="muted">
                    Loading member report...
                </p>

            </div>

        </div>
    `;

    document
        .getElementById("backToReportsBtn")
        .addEventListener("click", function () {
            reports();
        });

    try {

        const response = await fetch(
            "Reports/get_member_report.php"
        );

        const data = await response.json();

        console.log("Member report:", data);

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to load member report."
            );
        }

        renderMemberReport(
            data.members || []
        );

    } catch (error) {

        console.error(error);

        const container =
            document.getElementById(
                "memberReportContent"
            );

        if (container) {

            container.innerHTML = `
                <div class="empty-state">

                    <h3>Failed to Load Report</h3>

                    <p style="color:red;">
                        ${escapeHtml(error.message)}
                    </p>

                </div>
            `;
        }
    }
}

function renderMemberReport(members) {

    const container =
        document.getElementById(
            "memberReportContent"
        );

    if (!container) return;


    if (members.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No Members</h3>

                <p>
                    There are no members registered yet.
                </p>

            </div>
        `;

        return;
    }


    container.innerHTML = `

        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Member</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Mchezo Group</th>
                        <th>Join Date</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    ${members.map(member => `

                        <tr>

                            <td>
                                <strong>
                                    ${escapeHtml(
                                        member.full_name || "-"
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHtml(
                                    member.phone || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    member.email || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    member.group_name || "No Group"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    member.join_date || "-"
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    member.status || "-"
                                )}
                            </td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>

        <div
            style="
                margin-top:15px;
                font-size:13px;
            "
        >
            <strong>Total Members:</strong>
            ${members.length}
        </div>

    `;
}

function printReport() {

    const reportContent =
        document.querySelector(".card");

    if (!reportContent) {

        toast("No report available to print.");
        return;
    }

    const printWindow =
        window.open("", "_blank", "width=1000,height=700");

    if (!printWindow) {

        toast("Please allow pop-ups to print the report.");
        return;
    }

    printWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>KIJUMBE Report</title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    margin: 30px;
                    color: #111;
                }

                h1 {
                    margin-bottom: 5px;
                }

                p {
                    color: #555;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }

                th,
                td {
                    border: 1px solid #ccc;
                    padding: 8px;
                    text-align: left;
                    font-size: 13px;
                }

                th {
                    background: #f2f2f2;
                }

                .amount {
                    text-align: right;
                }

                .report-header {
                    margin-bottom: 20px;
                    border-bottom: 2px solid #222;
                    padding-bottom: 15px;
                }

                .print-date {
                    font-size: 12px;
                    color: #666;
                }

                @media print {

                    body {
                        margin: 15px;
                    }

                    button {
                        display: none;
                    }

                }

            </style>

        </head>

        <body>

            <div class="report-header">

                <h1>KIJUMBE</h1>

                <p>
                    Mchezo Management System
                </p>

                <div class="print-date">
                    Printed:
                    ${new Date().toLocaleString()}
                </div>

            </div>

            ${reportContent.innerHTML}

        </body>

        </html>

    `);

    printWindow.document.close();

    printWindow.focus();

    setTimeout(() => {

        printWindow.print();

    }, 300);

}



function settings() {
    content.innerHTML = `
        <div class="card">
            <div class="section-head">
                <div>
                    <h3>System Settings</h3>
                    <p>Basic KIJUMBE configuration for the Mchezo group.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>System Name</label>
                    <input value="KIJUMBE">
                </div>

                <div class="field">
                    <label>Currency</label>
                    <select>
                        <option>Tanzanian Shilling (TSh)</option>
                    </select>
                </div>

                <div class="field">
                    <label>Default Contribution</label>
                    <input value="20,000">
                </div>

                <div class="field">
                    <label>Timezone</label>
                    <input value="Africa/Dar_es_Salaam">
                </div>
            </div>

            <div class="actions">
                <button
                    class="btn btn-primary"
                    onclick="toast('Settings saved')">
                    Save Settings
                </button>
            </div>

            <hr style="border:0;border-top:1px solid var(--border);margin:25px 0">

            <h3 style="font-size:14px">User Roles</h3>

            <p class="muted" style="font-size:11px">
                Administrator · Treasurer · Mchezo Coordinator · Member
            </p>
        </div>

        <div class="card" style="margin-top:20px;">

            <div class="section-head">

                <div>
                    <h3>User Management</h3>
                    <p>
                        Manage KIJUMBE system accounts and assigned roles.
                    </p>
                </div>

                <button
                    class="btn btn-primary"
                    onclick="openAddUserModal()">
                    ＋ Add User
                </button>

            </div>

            <div id="usersContent">
                <p class="muted">Loading users...</p>
            </div>

        </div>
    `;

    loadUsers();
}

async function loadUsers() {

    const container = document.getElementById("usersContent");

    if (!container) return;

    try {

        const response = await fetch("Users/get_users.php");

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Failed to load users."
            );
        }

        window.usersList = data.users || [];

        renderUsers(window.usersList);

    } catch (error) {

        console.error(error);

        container.innerHTML = `
            <div class="empty-state">
                <h3>Failed to Load Users</h3>

                <p style="color:red;">
                    ${escapeHtml(error.message)}
                </p>
            </div>
        `;
    }
}

function renderUsers(users) {

    const container = document.getElementById("usersContent");

    if (!container) return;

    if (users.length === 0) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>No Users</h3>

                <p>
                    No system user accounts have been created yet.
                </p>

            </div>
        `;

        return;
    }

    container.innerHTML = `
        <div class="table-wrap">

            <table class="table">

<thead>

    <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Member</th>
        <th>Role</th>
        <th>Email</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

</thead>

<tbody>

    ${users.map(user => `

        <tr>

            <td>
                <strong>
                    ${escapeHtml(user.full_name || "-")}
                </strong>
            </td>

            <td>
                ${escapeHtml(user.username || "-")}
            </td>

            <td>
                ${escapeHtml(user.member_name || "No Member")}
            </td>

            <td>
                ${escapeHtml(user.role || "-")}
            </td>

            <td>
                ${escapeHtml(user.email || "-")}
            </td>

            <td>
                ${badge(
                    user.status === "active"
                        ? "Active"
                        : "Inactive"
                )}
            </td>

            <td>

    <button
        class="btn btn-light"
        onclick="editUser(${user.id})">
        Edit
    </button>

    ${
        Number(user.id) === Number(window.currentUserId)
        ? ""
        : `
            <button
                class="btn btn-light"
                onclick="resetUserPassword(${user.id})">
                Reset Password
            </button>

            ${
                user.status === "active"
                    ? `
                        <button
                            class="btn btn-light"
                            onclick="changeUserStatus(
                                ${user.id},
                                'inactive'
                            )">
                            Deactivate
                        </button>
                      `
                    : `
                        <button
                            class="btn btn-light"
                            onclick="changeUserStatus(
                                ${user.id},
                                'active'
                            )">
                            Reactivate
                        </button>
                      `
            }
        `
    }

</td>

                        </tr>

                    `).join("")}

                </tbody>

            </table>

        </div>

        <div style="margin-top:15px;font-size:13px;">

            <strong>Total Users:</strong>
            ${users.length}

        </div>
    `;
}

function showPage(p){

    document.querySelectorAll(".nav-item")
        .forEach(b =>
            b.classList.toggle(
                "active",
                b.dataset.page === p
            )
        );

    title.textContent = pages[p].title;
    subtitle.textContent = pages[p].sub;

    closeMobile();

    ({
        dashboard,
        mchezo,
        members,
        rounds,
        contributions,
        rotation,
        payments,
        meetings,
        fines,
        transactions,
        reports,
        auditLogs,
        settings
    }[p] || dashboard)();

}

function openAddUserModal() {

    openModal("Create User");

    const body = document.getElementById("modalBody");

    body.innerHTML = `
        <div>

            <div class="form-grid">

                <div class="field">
                    <label for="userFullName">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="userFullName"
                        placeholder="Enter full name"
                    >
                </div>

                <div class="field">
                    <label for="userEmail">
                        Email
                        <span class="muted">(Optional)</span>
                    </label>

                    <input
                        type="email"
                        id="userEmail"
                        placeholder="Enter email if available"
                    >
                </div>

                <div class="field">
                    <label for="userRole">
                        Role
                    </label>

                    <select id="userRole">

                        <option value="">
                            Select Role
                        </option>

                        <option value="admin">
                            Administrator
                        </option>

                        <option value="treasurer">
                            Treasurer
                        </option>

                        <option value="coordinator">
                            Coordinator
                        </option>

                        <option value="member">
                            Member
                        </option>

                    </select>
                </div>

                <div class="field">

                    <label for="userMember">
                        Member
                    </label>

                    <select id="userMember">

                        <option value="">
                            Select Member
                        </option>

                    </select>

                    <small
                        id="memberHelp"
                        class="muted">
                        Required for Treasurer,
                        Coordinator and Member.
                    </small>

                </div>

            </div>

            <div
                style="
                    margin-top:20px;
                    padding:15px;
                    border:1px solid var(--border);
                    border-radius:10px;
                    background:var(--surface);
                "
            >

                <strong>
                    🔐 Login credentials
                </strong>

                <p
                    class="muted"
                    style="margin-top:6px;"
                >
                    KIJUMBE will automatically generate
                    a username and secure temporary password
                    after the account is created.
                </p>

            </div>

            <div
                class="actions"
                style="margin-top:20px;"
            >

                <button
                    class="btn btn-light"
                    onclick="closeModal()">
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    onclick="saveUser()">
                    Create User
                </button>

            </div>

        </div>
    `;

    loadUserMembers();

    const roleSelect =
        document.getElementById("userRole");

    roleSelect.addEventListener(
        "change",
        updateUserMemberField
    );

    updateUserMemberField();
}

async function loadUserMembers() {

    const select = document.getElementById("userMember");

    if (!select) return;

    try {

        const response = await fetch("Members/get_members.php");

        const data = await response.json();

        if (!response.ok) {
            throw new Error("Failed to load members.");
        }

        const members = Array.isArray(data)
            ? data
            : data.members || [];

        select.innerHTML = `
            <option value="">
                Select Member
            </option>

            ${members.map(member => `
                <option value="${member.id}">
                    ${escapeHtml(member.full_name)}
                </option>
            `).join("")}
        `;

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Failed to load members
            </option>
        `;
    }
}

function updateUserMemberField() {

    const role = document.getElementById("userRole");
    const member = document.getElementById("userMember");
    const help = document.getElementById("memberHelp");

    if (!role || !member) return;

    if (role.value === "admin") {

        member.value = "";
        member.disabled = true;

        if (help) {
            help.textContent =
                "Member is not required for an Administrator.";
        }

    } else {

        member.disabled = false;

        if (help) {
            help.textContent =
                "Member is required for this role.";
        }
    }
}


async function saveUser() {

    const fullName = document
        .getElementById("userFullName")
        .value
        .trim();

    const email = document
        .getElementById("userEmail")
        .value
        .trim();

    const role = document
        .getElementById("userRole")
        .value;

    const memberId = document
        .getElementById("userMember")
        .value;


    /*
     * Basic validation
     */

    if (!fullName) {

        toast("Please enter the full name.");

        return;
    }


    if (!role) {

        toast("Please select a role.");

        return;
    }


    if (role !== "admin" && !memberId) {

        toast(
            "Please select a member for this role."
        );

        return;
    }


    /*
     * Prepare form data
     */

    const formData = new FormData();

    formData.append(
        "full_name",
        fullName
    );

    formData.append(
        "email",
        email
    );

    formData.append(
        "member_id",
        role === "admin"
            ? ""
            : memberId
    );

    formData.append(
        "role",
        role
    );


    try {

        const response = await fetch(
            "Users/add_user.php",
            {
                method: "POST",
                body: formData
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to create user."
            );
        }


        /*
         * Refresh user list
         */

        loadUsers();


        /*
         * Show generated credentials.
         */

        showGeneratedCredentials(
            data.credentials
        );


    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

function editUser(userId) {

    const user = window.usersList?.find(
        u => Number(u.id) === Number(userId)
    );

    if (!user) {
        toast("User information not found.");
        return;
    }

    openModal("Edit User");

    const body = document.getElementById("modalBody");

    body.innerHTML = `
        <div>

            <div class="form-grid">

                <div class="field">
                    <label for="editUserFullName">Full Name</label>

                    <input
                        type="text"
                        id="editUserFullName"
                        value="${escapeHtml(user.full_name || "")}"
                    >
                </div>

                <div class="field">
                    <label for="editUserUsername">Username</label>

                    <input
                        type="text"
                        id="editUserUsername"
                        value="${escapeHtml(user.username || "")}"
                    >
                </div>

                <div class="field">
                    <label for="editUserEmail">
                        Email <span class="muted">(Optional)</span>
                    </label>

                    <input
                        type="email"
                        id="editUserEmail"
                        value="${escapeHtml(user.email || "")}"
                        placeholder="Enter email if available"
                    >
                </div>

                <div class="field">
                    <label for="editUserRole">Role</label>

                    <select id="editUserRole">

                        <option value="admin"
                            ${user.role === "admin" ? "selected" : ""}>
                            Administrator
                        </option>

                        <option value="treasurer"
                            ${user.role === "treasurer" ? "selected" : ""}>
                            Treasurer
                        </option>

                        <option value="coordinator"
                            ${user.role === "coordinator" ? "selected" : ""}>
                            Coordinator
                        </option>

                        <option value="member"
                            ${user.role === "member" ? "selected" : ""}>
                            Member
                        </option>

                    </select>
                </div>

                <div class="field">

                    <label for="editUserMember">
                        Member
                    </label>

                    <select id="editUserMember">

                        <option value="">
                            Loading members...
                        </option>

                    </select>

                    <small
                        id="editMemberHelp"
                        class="muted">
                        Required for Treasurer, Coordinator and Member.
                    </small>

                </div>

            </div>

            <div class="actions" style="margin-top:20px;">

                <button
                    class="btn btn-light"
                    onclick="closeModal()">
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    onclick="submitUserUpdate(${user.id})">
                    Save Changes
                </button>

            </div>

        </div>
    `;

    loadEditUserMembers(user.member_id, user.role);

    const roleSelect = document.getElementById("editUserRole");

    roleSelect.addEventListener(
        "change",
        updateEditUserMemberField
    );
}

async function loadEditUserMembers(
    selectedMemberId,
    selectedRole
) {

    const select = document.getElementById("editUserMember");

    if (!select) return;

    try {

        const response = await fetch(
            "Members/get_members.php"
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error("Failed to load members.");
        }

        const members = Array.isArray(data)
            ? data
            : data.members || [];

        select.innerHTML = `
            <option value="">
                Select Member
            </option>

            ${members.map(member => `
                <option
                    value="${member.id}"
                    ${Number(member.id) === Number(selectedMemberId)
                        ? "selected"
                        : ""}
                >
                    ${escapeHtml(member.full_name)}
                </option>
            `).join("")}
        `;

        updateEditUserMemberField();

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Failed to load members
            </option>
        `;
    }
}

function updateEditUserMemberField() {

    const role = document.getElementById("editUserRole");
    const member = document.getElementById("editUserMember");
    const help = document.getElementById("editMemberHelp");

    if (!role || !member) return;

    if (role.value === "admin") {

        member.value = "";
        member.disabled = true;

        if (help) {
            help.textContent =
                "Member is not required for an Administrator.";
        }

    } else {

        member.disabled = false;

        if (help) {
            help.textContent =
                "Member is required for this role.";
        }
    }
}

async function submitUserUpdate(userId) {

    const fullName = document
        .getElementById("editUserFullName")
        .value
        .trim();

    const username = document
        .getElementById("editUserUsername")
        .value
        .trim();

    const email = document
        .getElementById("editUserEmail")
        .value
        .trim();

    const role = document
        .getElementById("editUserRole")
        .value;

    const memberId = document
        .getElementById("editUserMember")
        .value;


    if (!fullName) {
        toast("Please enter the full name.");
        return;
    }


    if (!username) {
        toast("Please enter a username.");
        return;
    }


    if (!role) {
        toast("Please select a role.");
        return;
    }


    if (role !== "admin" && !memberId) {
        toast("Please select a member for this role.");
        return;
    }


    const formData = new FormData();

    formData.append("user_id", userId);
    formData.append("full_name", fullName);
    formData.append("username", username);
    formData.append("email", email);
    formData.append(
        "member_id",
        role === "admin" ? "" : memberId
    );
    formData.append("role", role);


    try {

        const response = await fetch(
            "Users/update_user.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to update user."
            );
        }


        toast("User account updated successfully.");

        closeModal();

        loadUsers();


    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

async function changeUserStatus(userId, status) {

    const user = window.usersList?.find(
        u => Number(u.id) === Number(userId)
    );

    if (!user) {
        toast("User information not found.");
        return;
    }

    const action = status === "active"
        ? "reactivate"
        : "deactivate";

    const confirmed = confirm(
        `Are you sure you want to ${action} "${user.full_name}"?`
    );

    if (!confirmed) {
        return;
    }

    const formData = new FormData();

    formData.append("user_id", userId);
    formData.append("status", status);

    try {

        const response = await fetch(
            "Users/update_user_status.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                `Failed to ${action} user.`
            );
        }

        toast(data.message);

        loadUsers();

    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

function showGeneratedCredentials(credentials) {

    const body = document.getElementById("modalBody");

    if (!body) return;

    body.innerHTML = `

        <div>

            <div
                style="
                    text-align:center;
                    margin-bottom:20px;
                "
            >

                <div style="font-size:40px;margin-bottom:10px;">
                    ✅
                </div>

                <h3>User Account Created</h3>

                <p class="muted">
                    The login credentials have been generated successfully.
                </p>

            </div>


            <div
                style="
                    padding:18px;
                    border:1px solid var(--border);
                    border-radius:10px;
                "
            >

                <div style="margin-bottom:15px;">

                    <small class="muted">
                        Full Name
                    </small>

                    <div>
                        <strong>
                            ${escapeHtml(credentials.full_name)}
                        </strong>
                    </div>

                </div>


                <div style="margin-bottom:15px;">

                    <small class="muted">
                        Username
                    </small>

                    <div style="margin-top:4px;">

                        <code
                            id="generatedUsername"
                            style="font-size:15px;font-weight:bold;"
                        >
                            ${escapeHtml(credentials.username)}
                        </code>

                    </div>

                </div>


                <div>

                    <small class="muted">
                        Temporary Password
                    </small>

                    <div style="margin-top:4px;">

                        <code
                            id="generatedPassword"
                            style="font-size:15px;font-weight:bold;"
                        >
                            ${escapeHtml(credentials.password)}
                        </code>

                    </div>

                </div>

            </div>


            <div
                style="
                    margin-top:15px;
                    padding:12px;
                    border-radius:8px;
                    background:var(--surface);
                    font-size:13px;
                "
            >

                ⚠️ Give these credentials to the user.
                The password is stored securely as a hash
                and cannot be viewed again after this step.

            </div>


            <div
                class="actions"
                style="
                    margin-top:20px;
                    display:flex;
                    justify-content:flex-end;
                    gap:8px;
                "
            >

                <button
                    class="btn btn-light"
                    onclick="copyUserCredentials()"
                >
                    📋 Copy Credentials
                </button>

                <button
                    class="btn btn-primary"
                    onclick="closeModal()"
                >
                    Done
                </button>

            </div>

        </div>
    `;
}

function copyUserCredentials() {

    const username =
        document.getElementById("generatedUsername")
            ?.textContent
            .trim();

    const password =
        document.getElementById("generatedPassword")
            ?.textContent
            .trim();

    if (!username || !password) {

        toast("Credentials are not available.");

        return;
    }

    const credentials =
`KIJUMBE LOGIN CREDENTIALS

Username: ${username}
Password: ${password}`;


    /*
     * Modern clipboard API
     */
    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {

        navigator.clipboard.writeText(credentials)
            .then(() => {

                toast("Credentials copied successfully.");

            })
            .catch(() => {

                fallbackCopyCredentials(credentials);

            });

        return;
    }


    /*
     * Fallback for localhost / older browsers
     */
    fallbackCopyCredentials(credentials);
}

function fallbackCopyCredentials(text) {

    const textarea =
        document.createElement("textarea");

    textarea.value = text;

    textarea.style.position = "fixed";
    textarea.style.left = "-9999px";
    textarea.style.top = "0";

    document.body.appendChild(textarea);

    textarea.focus();
    textarea.select();

    try {

        const successful =
            document.execCommand("copy");

        if (successful) {

            toast("Credentials copied successfully.");

        } else {

            toast("Failed to copy credentials.");

        }

    } catch (error) {

        console.error(error);

        toast("Failed to copy credentials.");

    }

    document.body.removeChild(textarea);
}


function openModal(type,index=null){
 document.getElementById("modalTitle").textContent=type;
 const body=document.getElementById("modalBody");
 
if(type === "Add Member") {

    body.innerHTML = `
        <div class="form-grid">

            <div class="field">
                <label>Full Name</label>
                <input
                    id="mName"
                    placeholder="Enter full name"
                >
            </div>

            <div class="field">
                <label>Phone</label>
                <input
                    id="mPhone"
                    placeholder="0712345678"
                >
            </div>

            <div class="field">
                <label>Email</label>
                <input
                    id="mEmail"
                    type="email"
                    placeholder="example@gmail.com"
                >
            </div>

            <div class="field">
                <label>Join Date</label>
                <input
                    id="mJoinDate"
                    type="date"
                >
            </div>

          <div class="field">
              <label>Mchezo Group</label>

              <select id="mGroupId">
                  <option value="">Loading groups...</option>
              </select>
          </div>

        </div>

        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>

            <button
                class="btn btn-primary"
                onclick="saveMember()"
            >
                Save Member
            </button>

        </div>
    `;
loadGroups();}

else if (type === "Add Mchezo Group") {

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Group Name</label>

                <input
                    id="gName"
                    placeholder="Enter group name"
                >

            </div>


            <div class="field">

                <label>Contribution Amount</label>

                <input
                    id="gAmount"
                    type="number"
                    min="1"
                    placeholder="e.g. 50000"
                >

            </div>


            <div class="field">

                <label>Frequency</label>

                <select id="gFrequency">

                    <option value="">
                        Select frequency
                    </option>

                    <option value="weekly">
                        Weekly
                    </option>

                    <option value="monthly">
                        Monthly
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Cycle Length</label>

                <input
                    id="gCycle"
                    type="number"
                    min="1"
                    placeholder="Number of rounds"
                >

                </div>

                    <div class="field">

                <label>Maximum Members</label>

                <input
                    id="gMaxMembers"
                    type="number"
                    min="1"
                    placeholder="e.g. 10"
                >

                </div>


            <div class="field">

                <label>Start Date</label>

                <input
                    id="gStartDate"
                    type="date"
                >

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="saveMchezoGroup()"
            >
                Save Group
            </button>

        </div>

    `;

}

else if (type === "Create Round") {

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Mchezo Group</label>

                <select id="roundGroupId">

                    <option value="">
                        Loading groups...
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Round Number</label>

                <input
                    id="roundNumber"
                    type="number"
                    min="1"
                    placeholder="e.g. 1"
                >

            </div>


            <div class="field">

                <label>Due Date</label>

                <input
                    id="roundDueDate"
                    type="date"
                >

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="saveRound()"
            >
                Create Round
            </button>

        </div>

    `;

    
    loadRoundGroups();
}

else if (type === "Create Meeting") {

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Mchezo Group</label>

                <select id="meetingGroupId">

                    <option value="">
                        Loading groups...
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Meeting Title</label>

                <input
                    id="meetingTitle"
                    type="text"
                    placeholder="e.g. Monthly Mchezo Meeting"
                >

            </div>


            <div class="field">

                <label>Meeting Date</label>

                <input
                    id="meetingDate"
                    type="date"
                >

            </div>


            <div class="field">

                <label>Location</label>

                <input
                    id="meetingLocation"
                    type="text"
                    placeholder="e.g. Group office"
                >

            </div>


            <div
                class="field"
                style="grid-column:1/-1"
            >

                <label>Agenda</label>

                <textarea
                    id="meetingAgenda"
                    rows="4"
                    placeholder="Enter meeting agenda..."
                ></textarea>

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="saveMeeting()"
            >
                Save Meeting
            </button>

        </div>

    `;


    loadMeetingGroups();
}


else if (type === "Record Fine") {

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Member</label>

                <select id="fineMemberId">

                    <option value="">
                        Loading members...
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Meeting</label>

                <select id="fineMeetingId">

                    <option value="">
                        No specific meeting
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Amount</label>

                <input
                    id="fineAmount"
                    type="number"
                    min="1"
                    placeholder="e.g. 5000"
                >

            </div>


            <div class="field">

                <label>Reason</label>

                <input
                    id="fineReason"
                    type="text"
                    placeholder="e.g. Missed meeting"
                >

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="saveFine()"
            >
                Save Fine
            </button>

        </div>

    `;


    loadFineMembers();
    loadFineMeetings();
}

else if (type === "Record Transaction") {

    body.innerHTML = `

        <div class="form-grid">

            <div class="field">

                <label>Mchezo Group</label>

                <select id="transactionGroupId">

                    <option value="">
                        Loading groups...
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Transaction Type</label>

                <select id="transactionType">

                    <option value="">
                        Select type
                    </option>

                    <option value="contribution">
                        Contribution
                    </option>

                    <option value="payout">
                        Payout
                    </option>

                    <option value="fine">
                        Fine
                    </option>

                    <option value="other">
                        Other
                    </option>

                </select>

            </div>


            <div class="field">

                <label>Amount</label>

                <input
                    id="transactionAmount"
                    type="number"
                    min="1"
                    placeholder="e.g. 50000"
                >

            </div>


            <div class="field">

                <label>Transaction Date</label>

                <input
                    id="transactionDate"
                    type="datetime-local"
                >

            </div>


            <div
                class="field"
                style="grid-column:1/-1"
            >

                <label>Description</label>

                <textarea
                    id="transactionDescription"
                    rows="3"
                    placeholder="Enter transaction description..."
                ></textarea>

            </div>

        </div>


        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>


            <button
                class="btn btn-primary"
                onclick="saveTransaction()"
            >
                Save Transaction
            </button>

        </div>

    `;


    loadTransactionGroups();

    const dateInput =
    document.getElementById("transactionDate");

if (dateInput) {

    const now = new Date();

    const localDateTime =
        new Date(
            now.getTime() -
            now.getTimezoneOffset() * 60000
        )
        .toISOString()
        .slice(0, 16);

    dateInput.value = localDateTime;
}
}



else if(type==="Record Contribution"){
  body.innerHTML=`<div class="form-grid"><div class="field"><label>Member</label><select><option>Amina Juma</option><option>Baraka Hassan</option><option>Neema Peter</option><option>David John</option></select></div><div class="field"><label>Round</label><select><option>Round 4</option><option>Round 5</option></select></div><div class="field"><label>Amount</label><input value="20,000"></div><div class="field"><label>Payment Method</label><select><option>Manual</option><option>AzamPay Sandbox</option></select></div></div><div class="actions"><button class="btn btn-primary" onclick="closeModal();toast('Contribution recorded successfully')">Record Contribution</button></div>`;
 }else if(type==="Sandbox Payment"){
  body.innerHTML=`<div class="alert">This is a frontend demonstration only. The real PHP backend will call the official AzamPay Sandbox API and verify the provider response before posting the transaction.</div><div class="form-grid" style="margin-top:15px"><div class="field"><label>Member</label><select><option>Neema Peter</option><option>Amina Juma</option></select></div><div class="field"><label>Amount</label><input value="20,000"></div><div class="field"><label>Reference</label><input value="DEMO-${Date.now().toString().slice(-6)}"></div><div class="field"><label>Status</label><select><option>Successful (Demo)</option><option>Pending (Demo)</option><option>Failed (Demo)</option></select></div></div><div class="actions"><button class="btn btn-primary" onclick="closeModal();toast('Sandbox payment demo created')">Run Demo</button></div>`;
 }else{
  body.innerHTML=`<div class="form-grid"><div class="field"><label>Name / Title</label><input placeholder="Enter details"></div><div class="field"><label>Date</label><input type="date" value="2026-09-10"></div><div class="field"><label>Amount</label><input placeholder="0"></div><div class="field"><label>Status</label><select><option>Pending</option><option>Scheduled</option><option>Paid</option></select></div></div><div class="actions"><button class="btn btn-primary" onclick="closeModal();toast('Record saved successfully')">Save</button></div>`;
 }
 document.getElementById("modal").classList.remove("hidden");


 
}

async function saveMember() {

    const name = document.getElementById("mName").value.trim();
    const phone = document.getElementById("mPhone").value.trim();
    const email = document.getElementById("mEmail").value.trim();
    const joinDate = document.getElementById("mJoinDate").value;
    const groupId = document.getElementById("mGroupId").value;

    if (!name || !phone || !joinDate || !groupId) {
        toast("Please fill in all required fields");
        return;
    }

    const formData = new FormData();

    formData.append("full_name", name);
    formData.append("phone", phone);
    formData.append("email", email);
    formData.append("join_date", joinDate);
    formData.append("group_id", groupId);

    try {

        const response = await fetch("Members/add_member.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            toast(result.message);
            return;
        }

        closeModal();

        toast(result.message);

        showPage("members");

    } catch (error) {

        console.error(error);

        toast("Failed to add member");

    }
}


function editMember(index) {

    const member = state.members[index];

    openEditMemberModal(member);

}
function openEditMemberModal(member) {

    const body = document.getElementById("modalBody");

    body.innerHTML = `
        <div class="form-grid">

            <div class="field">
                <label>Full Name</label>
                <input
                    id="editName"
                    value="${member.name}"
                >
            </div>

            <div class="field">
                <label>Phone</label>
                <input
                    id="editPhone"
                    value="${member.phone}"
                >
            </div>

            <div class="field">
                <label>Email</label>
                <input
                    id="editEmail"
                    type="email"
                    value="${member.email || ""}"
                >
            </div>

            <div class="field">
                <label>Join Date</label>
                <input
                    id="editJoinDate"
                    type="date"
                    value="${member.joinDate || ""}"
                >
            </div>

            <div class="field">
                <label>Status</label>

                <select id="editStatus">

                    <option value="active"
                        ${member.status === "Active" ? "selected" : ""}>
                        Active
                    </option>

                    <option value="inactive"
                        ${member.status === "Inactive" ? "selected" : ""}>
                        Inactive
                    </option>

                </select>
            </div>

        </div>

        <div class="actions">

            <button
                class="btn btn-light"
                onclick="closeModal()"
            >
                Cancel
            </button>

            <button
                class="btn btn-primary"
                onclick="updateMember(${member.id})"
            >
                Update Member
            </button>

        </div>
    `;

    document.getElementById("modalTitle").textContent = "Edit Member";

    document.getElementById("modal").classList.remove("hidden");
}

async function updateMember(id) {

    const name = document.getElementById("editName").value.trim();
    const phone = document.getElementById("editPhone").value.trim();
    const email = document.getElementById("editEmail").value.trim();
    const joinDate = document.getElementById("editJoinDate").value;
    const status = document.getElementById("editStatus").value;

    if (!name || !phone || !joinDate) {
        toast("Please fill in all required fields");
        return;
    }

    const formData = new FormData();

    formData.append("id", id);
    formData.append("full_name", name);
    formData.append("phone", phone);
    formData.append("email", email);
    formData.append("join_date", joinDate);
    formData.append("status", status);

    try {

        const response = await fetch("Members/update_member.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            toast(result.message);
            return;
        }

        closeModal();

        toast(result.message);

        showPage("members");

    } catch (error) {

        console.error(error);

        toast("Failed to update member");

    }
}

async function resetUserPassword(userId) {

    const user = window.usersList?.find(
        u => Number(u.id) === Number(userId)
    );

    if (!user) {

        toast("User information not found.");

        return;
    }


    const confirmed = confirm(
        `Reset the password for "${user.full_name}"?`
    );


    if (!confirmed) {
        return;
    }


    const formData = new FormData();

    formData.append(
        "user_id",
        userId
    );


    try {

        const response = await fetch(
            "Users/reset_password.php",
            {
                method: "POST",
                body: formData
            }
        );


        const data = await response.json();


        console.log(
            "Reset password response:",
            data
        );


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Failed to reset password."
            );
        }


        showResetPasswordCredentials(
            data.credentials
        );


    } catch (error) {

        console.error(error);

        toast(error.message);
    }
}

function showResetPasswordCredentials(credentials) {

    const body =
        document.getElementById("modalBody");

    if (!body) return;


    body.innerHTML = `

        <div>

            <div
                style="
                    text-align:center;
                    margin-bottom:20px;
                "
            >

                <div style="font-size:40px;margin-bottom:10px;">
                    🔐
                </div>

                <h3>
                    Password Reset Successful
                </h3>

                <p class="muted">
                    A new temporary password has been generated.
                </p>

            </div>


            <div
                style="
                    padding:18px;
                    border:1px solid var(--border);
                    border-radius:10px;
                "
            >

                <div style="margin-bottom:15px;">

                    <small class="muted">
                        Full Name
                    </small>

                    <div>
                        <strong>
                            ${escapeHtml(
                                credentials.full_name
                            )}
                        </strong>
                    </div>

                </div>


                <div style="margin-bottom:15px;">

                    <small class="muted">
                        Username
                    </small>

                    <div>
                        <code
                            id="resetUsername"
                            style="
                                font-size:15px;
                                font-weight:bold;
                            "
                        >
                            ${escapeHtml(
                                credentials.username
                            )}
                        </code>
                    </div>

                </div>


                <div>

                    <small class="muted">
                        New Temporary Password
                    </small>

                    <div>
                        <code
                            id="resetPassword"
                            style="
                                font-size:15px;
                                font-weight:bold;
                            "
                        >
                            ${escapeHtml(
                                credentials.password
                            )}
                        </code>
                    </div>

                </div>

            </div>


            <div
                style="
                    margin-top:15px;
                    padding:12px;
                    border-radius:8px;
                    background:var(--surface);
                    font-size:13px;
                "
            >

                ⚠️ Give the new credentials to the user.
                The new password is stored securely and
                cannot be viewed again after this step.

            </div>


            <div
                class="actions"
                style="
                    margin-top:20px;
                    display:flex;
                    justify-content:flex-end;
                    gap:8px;
                "
            >

                <button
                    class="btn btn-light"
                    onclick="copyResetCredentials()"
                >
                    📋 Copy Credentials
                </button>


                <button
                    class="btn btn-primary"
                    onclick="closeModal()"
                >
                    Done
                </button>

            </div>

        </div>
    `;
}

function copyResetCredentials() {

    const username =
        document.getElementById("resetUsername")
            ?.textContent
            .trim();

    const password =
        document.getElementById("resetPassword")
            ?.textContent
            .trim();


    if (!username || !password) {

        toast("Credentials are not available.");

        return;
    }


    const credentials =
`KIJUMBE LOGIN CREDENTIALS

Username: ${username}
Password: ${password}`;


    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {

        navigator.clipboard.writeText(credentials)
            .then(() => {

                toast(
                    "Credentials copied successfully."
                );

            })
            .catch(() => {

                fallbackCopyCredentials(credentials);

            });

        return;
    }


    fallbackCopyCredentials(credentials);
}

function showResetPasswordCredentials(credentials) {

    openModal("Password Reset Successful");

    const body =
        document.getElementById("modalBody");

    if (!body) {
        console.error("modalBody was not found.");
        return;
    }

    body.innerHTML = `
        <div>

            <div style="
                text-align:center;
                margin-bottom:20px;
            ">

                <div style="
                    font-size:42px;
                    margin-bottom:10px;
                ">
                    🔐
                </div>

                <h3>Password Reset Successful</h3>

                <p class="muted">
                    A new temporary password has been generated.
                </p>

            </div>


            <div style="
                padding:18px;
                border:1px solid var(--border);
                border-radius:10px;
            ">

                <div style="margin-bottom:16px;">

                    <div class="muted" style="font-size:12px;">
                        Full Name
                    </div>

                    <div style="margin-top:4px;">
                        <strong>
                            ${escapeHtml(
                                credentials.full_name || "-"
                            )}
                        </strong>
                    </div>

                </div>


                <div style="margin-bottom:16px;">

                    <div class="muted" style="font-size:12px;">
                        Username
                    </div>

                    <div style="margin-top:4px;">
                        <code
                            id="resetUsername"
                            style="
                                font-size:15px;
                                font-weight:bold;
                            "
                        >
                            ${escapeHtml(
                                credentials.username || "-"
                            )}
                        </code>
                    </div>

                </div>


                <div>

                    <div class="muted" style="font-size:12px;">
                        New Temporary Password
                    </div>

                    <div style="margin-top:4px;">
                        <code
                            id="resetPassword"
                            style="
                                font-size:15px;
                                font-weight:bold;
                            "
                        >
                            ${escapeHtml(
                                credentials.password || "-"
                            )}
                        </code>
                    </div>

                </div>

            </div>


            <div style="
                margin-top:15px;
                padding:12px;
                border-radius:8px;
                background:var(--surface);
                font-size:13px;
            ">

                ⚠️ Copy these credentials now.
                The generated password cannot be viewed
                again after this window is closed.

            </div>


            <div
                class="actions"
                style="
                    margin-top:20px;
                    display:flex;
                    justify-content:flex-end;
                    gap:8px;
                "
            >

                <button
                    type="button"
                    class="btn btn-light"
                    onclick="copyResetCredentials()"
                >
                    📋 Copy Credentials
                </button>


                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="closeModal()"
                >
                    Done
                </button>

            </div>

        </div>
    `;

    /*
     * Make sure the modal is visible.
     */
    const modal = document.getElementById("modal");

    if (modal) {
        modal.classList.add("active");
    }
}

function closeModal(){document.getElementById("modal").classList.add("hidden")}
function toast(msg){const x=document.createElement("div");x.className="toast";x.textContent=msg;document.body.appendChild(x);setTimeout(()=>x.remove(),2500)}
function closeMobile(){if(window.innerWidth<=800){sidebar.classList.remove("mobile-open");overlay.classList.remove("show")}}
document.querySelectorAll(".nav-item").forEach(b=>b.addEventListener("click",()=>showPage(b.dataset.page)));
document.getElementById("menuBtn").addEventListener("click",()=>{
 if(window.innerWidth<=800){sidebar.classList.toggle("mobile-open");overlay.classList.toggle("show")}
 else {sidebar.classList.toggle("collapsed");main.classList.toggle("expanded")}
});
overlay.addEventListener("click",closeMobile);
document.getElementById("modalClose").addEventListener("click",closeModal);
document.getElementById("modal").addEventListener("click",e=>{if(e.target.id==="modal")
  closeModal()});

document.getElementById("logoutBtn").addEventListener("click", () => {
    window.location.href = "Auth/logout.php";
});

window.addEventListener("resize",()=>{if(window.innerWidth>800)
  {sidebar.classList.remove("mobile-open");
    overlay.classList.remove("show")}});
showPage("dashboard");


async function saveMchezoGroup() {

    const groupName =
        document.getElementById("gName").value.trim();

    const contributionAmount =
        document.getElementById("gAmount").value;

    const frequency =
        document.getElementById("gFrequency").value;

    const cycleLength =
        document.getElementById("gCycle").value;

    const maxMembers = 
        document.getElementById("gMaxMembers").value;

    const startDate =
        document.getElementById("gStartDate").value;



    if (
        !groupName ||
        !contributionAmount ||
        !frequency ||
        !cycleLength ||
        !maxMembers ||
        !startDate
    ) {

        toast("Please fill in all required fields.");

        return;
    }


    const formData = new FormData();

formData.append("group_name", groupName);
formData.append("contribution_amount", contributionAmount);
formData.append("frequency", frequency);
formData.append("cycle_length", cycleLength);
formData.append("max_members", maxMembers);
formData.append("start_date", startDate);


    try {

        const response = await fetch(
            "Mchezo/add_group.php",
            {
                method: "POST",
                body: formData
            }
        );


        const result = await response.json();


        if (!result.success) {

            toast(result.message);

            return;
        }


        closeModal();

        toast(result.message);

        showPage("mchezo");


    } catch (error) {

        console.error(error);

        toast("Failed to add Mchezo group.");

    }
}









