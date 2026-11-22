// ignore_for_file: deprecated_member_use
import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:qr_flutter/qr_flutter.dart';

import 'api.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(FamilyApp(api: FamilyApi()));
}

String moodLabel(String value, Copy l) {
  final labels = <String, List<String>>{
    'happy': ['سعيد', 'Happy'],
    'calm': ['هادئ', 'Calm'],
    'tired': ['متعب', 'Tired'],
    'sad': ['حزين', 'Sad'],
    'unsettled': ['غير مستقر', 'Unsettled'],
    'sleepy': ['يشعر بالنعاس', 'Sleepy'],
    'good': ['جيد', 'Good'],
  };
  final label = labels[value];
  return label == null ? value : l(label[0], label[1]);
}

String statusLabel(String value, Copy l) {
  final labels = <String, List<String>>{
    'active': ['نشط', 'Active'],
    'inactive': ['غير نشط', 'Inactive'],
    'sent': ['صادرة', 'Issued'],
    'paid': ['مدفوعة', 'Paid'],
    'draft': ['مسودة', 'Draft'],
    'void': ['ملغاة', 'Void'],
    'minor': ['بسيط', 'Minor'],
    'moderate': ['متوسط', 'Moderate'],
    'serious': ['خطير', 'Serious'],
    'severe': ['شديد', 'Severe'],
  };
  final label = labels[value];
  return label == null ? value : l(label[0], label[1]);
}

const green = Color(0xFF285D4D);
const background = Color(0xFFF6F8F5);
typedef Copy = String Function(String ar, String en);

class FamilyApp extends StatefulWidget {
  final FamilyApi api;
  const FamilyApp({super.key, required this.api});
  @override
  State<FamilyApp> createState() => _FamilyAppState();
}

class _FamilyAppState extends State<FamilyApp> {
  final navigatorKey = GlobalKey<NavigatorState>();
  bool arabic = true, ready = false;
  String l(String ar, String en) => arabic ? ar : en;
  @override
  void initState() {
    super.initState();
    widget.api.onExpired = () {
      if (mounted) {
        navigatorKey.currentState?.popUntil((route) => route.isFirst);
        setState(() {});
      }
    };
    widget.api.restore().whenComplete(() {
      if (mounted) setState(() => ready = true);
    });
  }

  @override
  void dispose() {
    widget.api.onExpired = null;
    widget.api.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      debugShowCheckedModeBanner: false,
      title: 'Nursery Family',
      locale: Locale(arabic ? 'ar' : 'en'),
      supportedLocales: const [Locale('ar'),Locale('en')],
      localizationsDelegates: const [GlobalMaterialLocalizations.delegate,GlobalWidgetsLocalizations.delegate,GlobalCupertinoLocalizations.delegate],
      theme: ThemeData(
        visualDensity: VisualDensity.standard,
        materialTapTargetSize: MaterialTapTargetSize.padded,
        textButtonTheme: TextButtonThemeData(
          style: TextButton.styleFrom(minimumSize: const Size(48, 48)),
        ),
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: green),
        scaffoldBackgroundColor: background,
        fontFamily: 'Cairo',
        fontFamilyFallback: const ['CairoLatin'],
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            backgroundColor: green,
            minimumSize: const Size(48, 48),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          ),
        ),
      ),
      builder: (context, child) => Directionality(
        textDirection: arabic ? TextDirection.rtl : TextDirection.ltr,
        child: child!,
      ),
      home: !ready
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : widget.api.token == null
          ? LoginPage(
              api: widget.api,
              l: l,
              toggle: () => setState(() => arabic = !arabic),
              signedIn: () => setState(() {}),
            )
          : HomePage(
              api: widget.api,
              l: l,
              toggle: () => setState(() => arabic = !arabic),
              signedOut: () => setState(() {}),
            ),
    );
  }
}

class LoginPage extends StatefulWidget {
  final FamilyApi api;
  final Copy l;
  final VoidCallback toggle, signedIn;
  const LoginPage({
    super.key,
    required this.api,
    required this.l,
    required this.toggle,
    required this.signedIn,
  });
  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final code = TextEditingController(),
      email = TextEditingController(),
      password = TextEditingController();
  bool busy = false;
  String? error;
  @override
  void dispose() {
    code.dispose();
    email.dispose();
    password.dispose();
    super.dispose();
  }

  Future<void> login() async {
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await widget.api.login(code.text, email.text, password.text);
      password.clear();
      widget.signedIn();
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = widget.l;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nursery'),
        actions: [
          TextButton(
            onPressed: widget.toggle,
            child: const Text('عربي / English'),
          ),
        ],
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 480),
          child: ListView(
            padding: const EdgeInsets.all(24),
            shrinkWrap: true,
            children: [
              if (widget.api.signOutWarning != null) ...[
                Text(
                  l(
                    'تم الخروج من هذا الجهاز. تعذر تأكيد إلغاء الجلسة على الخادم. تواصل مع الحضانة إن كنت تشك في وصول آخر.',
                    widget.api.signOutWarning!,
                  ),
                  style: const TextStyle(color: Colors.red),
                ),
                const SizedBox(height: 16),
              ],
              Image.asset('assets/brand/nursery-icon.png', height: 80),
              const SizedBox(height: 20),
              Text(
                l('أهلاً بالأسرة', 'Welcome, family'),
                style: Theme.of(context).textTheme.headlineMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
              Text(l('البيت والحضانة، أقرب.', 'Home and nursery, closer.')),
              const SizedBox(height: 26),
              TextField(
                controller: code,
                decoration: InputDecoration(
                  labelText: l('رمز الحضانة', 'Nursery code'),
                ),
                textDirection: TextDirection.ltr,
                autocorrect: false,
              ),
              const SizedBox(height: 16),
              TextField(
                controller: email,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.username],
                textDirection: TextDirection.ltr,
                decoration: InputDecoration(
                  labelText: l('البريد الإلكتروني', 'Email'),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: password,
                obscureText: true,
                autofillHints: const [AutofillHints.password],
                onSubmitted: (_) {
                  if (!busy) login();
                },
                decoration: InputDecoration(
                  labelText: l('كلمة المرور', 'Password'),
                ),
              ),
              const SizedBox(height: 16),
              if (error != null) ErrorNotice(error!),
              FilledButton(
                onPressed: busy ? null : login,
                child: Text(
                  busy
                      ? l('جارٍ الدخول', 'Signing in')
                      : l('تسجيل الدخول', 'Sign in'),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                l(
                  'تحتاج دعوة معتمدة من الحضانة قبل أول دخول. فعّلها في رابط الأسرة، ثم استخدم الحساب هنا.',
                  'Activate a nursery-approved family invitation on the web before your first sign-in here.',
                ),
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: 16),
              Text(
                Uri.parse(widget.api.baseUrl).host,
                textDirection: TextDirection.ltr,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class HomePage extends StatefulWidget {
  final FamilyApi api;
  final Copy l;
  final VoidCallback toggle, signedOut;
  const HomePage({
    super.key,
    required this.api,
    required this.l,
    required this.toggle,
    required this.signedOut,
  });
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  late Future<Map<String, dynamic>> future;
  @override
  void initState() {
    super.initState();
    future = widget.api.request('me');
  }

  void refresh() {
    setState(() => future = widget.api.request('me'));
  }

  Future<void> logout() async {
    try {
      await widget.api.logout();
    } catch (_) {}
    if (mounted) widget.signedOut();
  }

  @override
  Widget build(BuildContext context) {
    final l = widget.l;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nursery'),
        actions: [
          IconButton(
            onPressed: refresh,
            tooltip: l('تحديث', 'Refresh'),
            icon: const Icon(Icons.refresh),
          ),
          TextButton(onPressed: widget.toggle, child: const Text('عربي / EN')),
          IconButton(
            onPressed: logout,
            tooltip: l('خروج', 'Log out'),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, s) {
          if (s.hasError) return RetryNotice(s.error.toString(), refresh, l);
          if (!s.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final d = s.data!;
          final children = d['children'] as List;
          return Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 920),
              child: RefreshIndicator(
                onRefresh: () async {
                  refresh();
                  await future;
                },
                child: ListView(
                  padding: const EdgeInsets.all(20),
                  children: [
                    Text(
                      d['nursery']['name'],
                      style: Theme.of(context).textTheme.labelLarge,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      l('كل يوم، أقرب.', 'Closer, every day.'),
                      style: Theme.of(context).textTheme.headlineMedium
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 10),
                    Text('${l('أهلاً', 'Welcome')}, ${d['account']['name']}'),
                    const SizedBox(height: 26),
                    if (children.isEmpty)
                      Panel(
                        child: Text(
                          l(
                            'لا يوجد طفل مرتبط. تواصل مع إدارة الحضانة لمراجعة وصولك.',
                            'No child is linked. Contact nursery management to review your access.',
                          ),
                        ),
                      ),
                    ...children.map(
                      (c) => Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: Card(
                          clipBehavior: Clip.antiAlias,
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(20),
                            leading: CircleAvatar(
                              backgroundColor: const Color(0xFFDFECDF),
                              child: Text(c['name'].toString().substring(0, 1)),
                            ),
                            title: Text(
                              c['name'],
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            subtitle: Text(
                              l(
                                'اليوميات والحضور والرسائل',
                                'Care, attendance and messages',
                              ),
                            ),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) =>
                                    ChildPage(api: widget.api, child: c, l: l),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      l('من الحضانة', 'From your nursery'),
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 12),
                    ...(d['announcements'] as List).map(
                      (a) => Panel(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              a['title'],
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(a['body']),
                          ],
                        ),
                      ),
                    ),
                    if ((d['announcements'] as List).isEmpty)
                      Text(
                        l('لا توجد إعلانات جديدة.', 'No announcements yet.'),
                      ),
                    const SizedBox(height: 30),
                    Text(
                      l(
                        'الرسائل ليست خدمة طوارئ. تواصل مباشرة مع الحضانة في الحالات العاجلة.',
                        'Messages are not an emergency service. Contact the nursery directly for urgent matters.',
                      ),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class ChildPage extends StatefulWidget {
  final FamilyApi api;
  final Map<String, dynamic> child;
  final Copy l;
  const ChildPage({
    super.key,
    required this.api,
    required this.child,
    required this.l,
  });
  @override
  State<ChildPage> createState() => _ChildPageState();
}

class _ChildPageState extends State<ChildPage>
    with SingleTickerProviderStateMixin {
  late Future<Map<String, dynamic>> future;
  late TabController tabs;
  final message = TextEditingController(), reason = TextEditingController();
  DateTime? from, to;
  bool busy = false;
  String get path => 'children/${widget.child['id']}';
  @override
  void initState() {
    super.initState();
    tabs = TabController(length: 4, vsync: this);
    future = widget.api.request(path);
  }

  @override
  void dispose() {
    tabs.dispose();
    message.dispose();
    reason.dispose();
    super.dispose();
  }

  void refresh() {
    setState(() => future = widget.api.request(path));
  }

  Future<void> action(String suffix, Map<String, dynamic> data) async {
    if (busy) return;
    setState(() => busy = true);
    try {
      await widget.api.request('$path/$suffix', body: data);
      if (!mounted) return;
      if (suffix == 'messages') message.clear();
      refresh();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> cancelAbsence(int id) async {
    final l = widget.l;
    final yes = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: Text(l('إلغاء إخطار الغياب', 'Cancel absence notice')),
        content: Text(
          l(
            'لن يتغير الحضور أو الرسوم. يمكنك إضافة إخطار جديد لتغيير المواعيد.',
            'Attendance and fees will not change. Add a new notice to change the dates.',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(c, false),
            child: Text(l('رجوع', 'Keep notice')),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(c, true),
            child: Text(l('إلغاء الإخطار', 'Cancel notice')),
          ),
        ],
      ),
    );
    if (yes == true) await action('absences/$id/cancel', {'confirmed': true});
  }

  Future<void> ack(int id) async {
    final l = widget.l;
    final yes = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: Text(l('إقرار بالاطلاع', 'Acknowledge incident')),
        content: Text(
          l(
            'أؤكد أنني قرأت وصف الحادثة. الإقرار لا يغيّر السجل ولا يحل محل التواصل مع الحضانة.',
            'I confirm I have read this incident. Acknowledgment does not change the record or replace speaking with the nursery.',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(c, false),
            child: Text(l('إلغاء', 'Cancel')),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(c, true),
            child: Text(l('قرأت الحادثة', 'I have read it')),
          ),
        ],
      ),
    );
    if (yes == true) {
      await action('incidents/$id/acknowledge', {'confirmed': true});
    }
  }

  Future<void> pickup() async {
    if (busy) return;
    setState(() => busy = true);
    final l = widget.l;
    try {
      final d = await widget.api.request('$path/pickup-token', body: {});
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => PickupDialog(
          token: d['token'],
          expires: DateTime.parse(d['expires_at']),
          l: l,
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> pickDate(bool start, String today) async {
    final now = DateTime.parse(today);
    var initial = (start ? from : to) ?? now;
    if (initial.isBefore(now)) initial = now;
    final d = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (d != null && mounted) setState(() => start ? from = d : to = d);
  }

  String date(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  Widget section(String text) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 16),
    child: Text(text, style: Theme.of(context).textTheme.titleLarge),
  );
  Widget care(Map<String, dynamic> d) {
    final l = widget.l;
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          '${d['range']['from']} · ${d['range']['to']}',
          textDirection: TextDirection.ltr,
        ),
        section(l('يوميات شاركتها الحضانة', 'Shared care updates')),
        if ((d['care'] as List).isEmpty)
          Text(
            l(
              'لم تشارك الحضانة يوميات لهذه الفترة بعد.',
              'No care updates shared for this range yet.',
            ),
          ),
        ...(d['care'] as List).map(
          (a) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  a['date'],
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                ...['meals', 'naps', 'mood', 'activities', 'notes']
                    .where((k) => (a[k] ?? '').toString().isNotEmpty)
                    .map(
                      (k) => Padding(
                        padding: const EdgeInsets.only(top: 10),
                        child: Text(
                          '${{'meals': l('الوجبات', 'Meals'), 'naps': l('النوم', 'Naps'), 'mood': l('المزاج', 'Mood'), 'activities': l('الأنشطة', 'Activities'), 'notes': l('ملاحظات', 'Notes')}[k]}: ${k == 'mood' ? moodLabel(a[k].toString(), l) : a[k]}',
                        ),
                      ),
                    ),
              ],
            ),
          ),
        ),
        section(l('الحضور المسجل', 'Recorded attendance')),
        Text('${l('بتوقيت الحضانة', 'In nursery time')}: ${d['timezone']}'),
        ...(d['attendance'] as List).map(
          (a) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  a['date'],
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                Text('${l('حضور', 'Arrival')}: ${a['check_in_at'] ?? '—'}'),
                Text('${l('استلام', 'Pickup')}: ${a['check_out_at'] ?? '—'}'),
              ],
            ),
          ),
        ),
        section(l('الحوادث المشتركة', 'Shared incidents')),
        ...(d['incidents'] as List).map(
          (i) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${statusLabel(i['severity']?.toString() ?? '', l)} · ${i['occurred_at']}',
                ),
                const SizedBox(height: 8),
                Text(i['description']),
                const SizedBox(height: 12),
                i['acknowledged'] == true
                    ? Text(
                        l(
                          'تم إقرارك بالاطلاع',
                          'You acknowledged this incident',
                        ),
                      )
                    : FilledButton(
                        onPressed: busy ? null : () => ack(i['id']),
                        child: Text(
                          l('قرأت الحادثة', 'I have read this incident'),
                        ),
                      ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget messages(Map<String, dynamic> d) {
    final l = widget.l;
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          l(
            'هذه محادثة حسابك فقط مع الحضانة. ليست قناة طوارئ.',
            'Only your account and nursery management see this conversation. Not an emergency channel.',
          ),
        ),
        ...(d['messages'] as List).map(
          (m) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  m['staff_id'] == null
                      ? l('أنت', 'You')
                      : l('فريق الحضانة', 'Nursery team'),
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                Text(
                  m['created_at'],
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const SizedBox(height: 8),
                Text(m['body']),
              ],
            ),
          ),
        ),
        const SizedBox(height: 20),
        TextField(
          controller: message,
          minLines: 3,
          maxLines: 6,
          maxLength: 4000,
          decoration: InputDecoration(labelText: l('رسالتك', 'Your message')),
        ),
        const SizedBox(height: 12),
        FilledButton(
          onPressed: busy
              ? null
              : () {
                  if (message.text.trim().isNotEmpty) {
                    action('messages', {'body': message.text});
                  }
                },
          child: Text(l('إرسال للحضانة', 'Send to nursery')),
        ),
      ],
    );
  }

  Widget billing(Map<String, dynamic> d) {
    final l = widget.l;
    if (d['can_view_billing'] != true) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            l(
              'لم تمنح الحضانة هذا الحساب صلاحية الرسوم.',
              'The nursery has not enabled billing access for this account.',
            ),
          ),
        ),
      );
    }
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          l(
            'دفعات مسجلة وليست إثبات تحويل بنكي. لا يتم تحصيل أموال من التطبيق حالياً.',
            'Recorded payments are not proof of a bank transfer. This app does not currently collect money.',
          ),
        ),
        ...(d['invoices'] as List).map(
          (i) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${l('فاتورة', 'Invoice')} #${i['id']}',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(i['description'] ?? ''),
                const Divider(),
                Text('${l('المبلغ', 'Amount')}: ${i['amount']}'),
                Text('${l('المتبقي', 'Outstanding')}: ${i['outstanding']}'),
                Text('${l('الاستحقاق', 'Due')}: ${i['due_date']}'),
                Text(
                  '${l('الحالة', 'Status')}: ${statusLabel(i['status'].toString(), l)}',
                ),
                ...(i['payments'] as List).map(
                  (p) => Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: Text(
                      '${l('إيصال دفعة مسجلة', 'Recorded payment receipt')} #${p['id']} · ${p['amount']} · ${p['paid_at']}',
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget planning(Map<String, dynamic> d) {
    final l = widget.l;
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        if (d['can_request_pickup'] == true)
          Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  l('رمز استلام مؤقت', 'Temporary pickup code'),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(
                  l(
                    'صالح لدقيقتين، وللمستلم المعتمد فقط. يجب التحقق من الهوية عند الباب.',
                    'Valid for two minutes, only for the approved pickup person. Physical identity must be checked at the door.',
                  ),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: busy ? null : pickup,
                  child: Text(l('إنشاء الرمز', 'Create code')),
                ),
              ],
            ),
          ),
        section(l('مهام الأسرة', 'Family tasks')),
        ...(d['tasks'] as List).map(
          (t) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t['title']),
                if (t['due_date'] != null) Text(t['due_date']),
                t['completed_at'] != null
                    ? Text(l('مكتملة', 'Completed'))
                    : TextButton(
                        onPressed: busy
                            ? null
                            : () => action('tasks/${t['id']}/complete', {}),
                        child: Text(l('تمت المهمة', 'Mark complete')),
                      ),
              ],
            ),
          ),
        ),
        section(l('غياب مخطط', 'Planned absence')),
        Text(
          l(
            'إخطار لا يمحو الحضور ولا يلغي الرسوم تلقائياً.',
            'A notice does not erase attendance or automatically waive fees.',
          ),
        ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            OutlinedButton(
              onPressed: () => pickDate(true, d['today']),
              child: Text(from == null ? l('من', 'From') : date(from!)),
            ),
            OutlinedButton(
              onPressed: () => pickDate(false, d['today']),
              child: Text(to == null ? l('إلى', 'To') : date(to!)),
            ),
          ],
        ),
        const SizedBox(height: 16),
        TextField(
          controller: reason,
          maxLength: 500,
          decoration: InputDecoration(
            labelText: l('ملاحظة اختيارية', 'Optional note'),
          ),
        ),
        FilledButton(
          onPressed: busy || from == null || to == null
              ? null
              : () => action('absences', {
                  'starts_on': date(from!),
                  'ends_on': date(to!),
                  'reason': reason.text,
                }),
          child: Text(l('إخطار الحضانة', 'Notify nursery')),
        ),
        ...(d['absences'] as List).map(
          (a) => Panel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${a['starts_on']} · ${a['ends_on']}'),
                Text(a['reason'] ?? ''),
                if (a['can_cancel'] == true)
                  TextButton(
                    onPressed: busy ? null : () => cancelAbsence(a['id']),
                    child: Text(
                      l('إلغاء إخطار الغياب', 'Cancel absence notice'),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final l = widget.l;
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.child['name']),
        actions: [
          IconButton(
            onPressed: refresh,
            tooltip: l('تحديث', 'Refresh'),
            icon: const Icon(Icons.refresh),
          ),
        ],
        bottom: TabBar(
          controller: tabs,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          tabs: [
            Tab(text: l('اليوم', 'Today')),
            Tab(text: l('الرسائل', 'Messages')),
            Tab(text: l('الرسوم', 'Fees')),
            Tab(text: l('المتابعة', 'Planning')),
          ],
        ),
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 920),
          child: FutureBuilder<Map<String, dynamic>>(
            future: future,
            builder: (context, s) {
              if (s.hasError) {
                return RetryNotice(s.error.toString(), refresh, l);
              }
              if (!s.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              return TabBarView(
                controller: tabs,
                children: [
                  care(s.data!),
                  messages(s.data!),
                  billing(s.data!),
                  planning(s.data!),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class PickupDialog extends StatefulWidget {
  final String token;
  final DateTime expires;
  final Copy l;
  const PickupDialog({
    super.key,
    required this.token,
    required this.expires,
    required this.l,
  });
  @override
  State<PickupDialog> createState() => _PickupDialogState();
}

class _PickupDialogState extends State<PickupDialog> {
  Timer? timer;
  bool expired = false;
  @override
  void initState() {
    super.initState();
    final duration = widget.expires.difference(DateTime.now());
    expired = duration.isNegative;
    if (!expired) {
      timer = Timer(duration, () {
        if (mounted) setState(() => expired = true);
      });
    }
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l = widget.l;
    return AlertDialog(
      title: Text(l('رمز استلام مؤقت', 'Temporary pickup code')),
      content: SizedBox(
        width: 260,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (!expired)
                QrImageView(
                  data: 'nursery-pickup:v1:${widget.token}',
                  size: 240,
                  backgroundColor: Colors.white,
                ),
              Text(
                expired
                    ? l(
                        'انتهت الصلاحية. أنشئ رمزاً جديداً.',
                        'Expired. Create a new code.',
                      )
                    : l(
                        'ينتهي خلال دقيقتين. الرمز لا يحل محل التحقق من الهوية.',
                        'Expires within two minutes. It does not replace physical identity verification.',
                      ),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(l('إغلاق', 'Close')),
        ),
      ],
    );
  }
}

class Panel extends StatelessWidget {
  final Widget child;
  const Panel({super.key, required this.child});
  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.symmetric(vertical: 8),
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: SizedBox(width: double.infinity, child: child),
    ),
  );
}

class ErrorNotice extends StatelessWidget {
  final String text;
  const ErrorNotice(this.text, {super.key});
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 12),
    child: Text(
      text,
      style: const TextStyle(color: Color(0xFFB42318)),
      semanticsLabel: text,
    ),
  );
}

class RetryNotice extends StatelessWidget {
  final String message;
  final VoidCallback retry;
  final Copy l;
  const RetryNotice(this.message, this.retry, this.l, {super.key});
  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ErrorNotice(message),
          FilledButton(
            onPressed: retry,
            child: Text(l('حاول مرة أخرى', 'Try again')),
          ),
        ],
      ),
    ),
  );
}
